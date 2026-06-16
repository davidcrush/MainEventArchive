<?php

namespace App\Services\Wikipedia;

use App\Data\ParsedWikipediaMatch;
use RuntimeException;

class WikipediaResultsParser
{
    public function __construct(
        private readonly WikipediaEventSectionExtractor $sectionExtractor,
    ) {}

    /**
     * @return list<ParsedWikipediaMatch>
     */
    public function parse(string $wikitext, string ...$eventScopeHeadings): array
    {
        $content = $wikitext;
        $scopeHeadings = array_values(array_unique(array_filter(
            $eventScopeHeadings,
            static fn (string $heading): bool => trim($heading) !== '',
        )));

        if ($scopeHeadings !== []) {
            $scopedContent = $this->sectionExtractor->extract($wikitext, ...$scopeHeadings);

            if ($scopedContent !== null) {
                $content = $scopedContent;
            }
        }

        $resultsSection = $this->extractResultsSection($content);

        if ($resultsSection === null) {
            if ($scopeHeadings !== [] && preg_match('/\{\{Pro [Ww]restling results table/i', $content) === 1) {
                $resultsSection = $content;
            } else {
                throw new RuntimeException('No Results section found on Wikipedia page.');
            }
        }

        if (preg_match('/\{\{Pro [Ww]restling results table/i', $resultsSection) === 1) {
            return $this->parseProWrestlingResultsTemplate($resultsSection);
        }

        return $this->parseWikitable($resultsSection);
    }

    private function extractResultsSection(string $wikitext): ?string
    {
        if (preg_match('/==\s*Results\s*==(.+?)(?=\n==[^=]|$)/is', $wikitext, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @return list<ParsedWikipediaMatch>
     */
    private function parseProWrestlingResultsTemplate(string $section): array
    {
        if (preg_match('/\{\{Pro [Ww]restling results table(.*?)\n\s*\}\}/is', $section, $templateMatch) !== 1) {
            throw new RuntimeException('Results section does not contain a Pro Wrestling results table.');
        }

        $body = $templateMatch[1];
        $parameters = $this->parseTemplateParameters($body);
        $matches = [];
        $matchIndexes = [];

        foreach (array_keys($parameters) as $key) {
            if (preg_match('/^match(\d+)$/', $key, $indexMatch) === 1) {
                $matchIndexes[] = (int) $indexMatch[1];
            }
        }

        sort($matchIndexes);

        foreach ($matchIndexes as $index) {
            $matchLine = $parameters["match{$index}"] ?? null;

            if ($matchLine === null || trim($matchLine) === '') {
                continue;
            }

            $stipulation = $parameters["stip{$index}"] ?? '';
            $time = $parameters["time{$index}"] ?? '';
            $note = strtolower(trim($parameters["note{$index}"] ?? ''));

            $matches[] = $this->parseMatchLine(
                $matchLine,
                $stipulation,
                $time,
                $index,
                $this->resolveIsPpv($note),
            );
        }

        if ($matches === []) {
            throw new RuntimeException('Pro Wrestling results table contained no matches.');
        }

        return $matches;
    }

    /**
     * @return array<string, string>
     */
    private function parseTemplateParameters(string $body): array
    {
        $parameters = [];

        foreach (preg_split('/\n\|/', "\n".$body) as $line) {
            $line = ltrim($line, '| ');

            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $parameters[trim($key)] = trim($value);
        }

        return $parameters;
    }

    private function parseMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        bool $isPpv = true,
    ): ParsedWikipediaMatch {
        $entrantNames = [];

        if (preg_match('/\s+won\s+by\s+(?:"last eliminating"|last eliminating)\s+/i', $matchLine) === 1) {
            $entrantNames = $this->extractBattleRoyalEntrantNames($matchLine);
        }

        $matchLine = $this->removeReferenceTags($matchLine);

        if (preg_match('/\s+defeated\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseStandardMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv);
        }

        if (preg_match('/\s+won\s+by\s+(?:"last eliminating"|last eliminating)\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseLastEliminationMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv, $entrantNames);
        }

        if (preg_match('/\s+ended\s+in\s+a\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseNoContestMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv);
        }

        throw new RuntimeException("Could not parse winner/loser for match {$cardOrder}.");
    }

    /**
     * @param  array<int, array{0: non-empty-string, 1: int}>  $splitMatch
     */
    private function parseStandardMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        array $splitMatch,
        bool $isPpv,
    ): ParsedWikipediaMatch {
        $winnerRaw = trim(substr($matchLine, 0, $splitMatch[0][1]));
        $loserAndFinishRaw = trim(substr($matchLine, $splitMatch[0][1] + strlen($splitMatch[0][0])));

        $finish = null;
        if (preg_match('/\s+by\s+(\[\[[^\]]+\]\]|.+)$/i', $loserAndFinishRaw, $finishMatch) === 1) {
            $finish = $this->stripWikiMarkup($finishMatch[1]);
            $loserRaw = trim(substr($loserAndFinishRaw, 0, -strlen($finishMatch[0][0])));
        } else {
            $loserRaw = $loserAndFinishRaw;
        }

        $cleanMatchLine = $this->stripWikiMarkup($matchLine);

        if (preg_match('/\s+vs\.?\s+/i', $cleanMatchLine) && ! str_contains(strtolower($cleanMatchLine), 'defeated')) {
            throw new RuntimeException("Unsupported result format at match {$cardOrder}: draws or non-decisive results must be entered manually.");
        }

        $splitLoserIntoSides = $this->isTriangleMatch($stipulation);

        $participants = array_merge(
            $this->parseTeamsFromSide($winnerRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($loserRaw, 2, combineSegments: ! $splitLoserIntoSides),
        );

        return $this->buildParsedMatch(
            cardOrder: $cardOrder,
            stipulation: $stipulation,
            time: $time,
            participants: $participants,
            finish: $finish ?? 'pinfall',
            isPpv: $isPpv,
        );
    }

    /**
     * @param  array<int, array{0: non-empty-string, 1: int}>  $splitMatch
     */
    private function parseLastEliminationMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        array $splitMatch,
        bool $isPpv,
        array $entrantNames = [],
    ): ParsedWikipediaMatch {
        $winnerRaw = trim(substr($matchLine, 0, $splitMatch[0][1]));
        $runnerUpRaw = trim(substr($matchLine, $splitMatch[0][1] + strlen($splitMatch[0][0])));

        $participants = array_merge(
            $this->parseTeamsFromSide($winnerRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($runnerUpRaw, 2, combineSegments: true),
        );

        if ($participants === []) {
            throw new RuntimeException("Could not parse winner for battle royal match {$cardOrder}.");
        }

        return $this->buildParsedMatch(
            cardOrder: $cardOrder,
            stipulation: $stipulation,
            time: $time,
            participants: $participants,
            finish: 'last_elimination',
            isPpv: $isPpv,
            entrantNames: $entrantNames,
        );
    }

    /**
     * @param  array<int, array{0: non-empty-string, 1: int}>  $splitMatch
     */
    private function parseNoContestMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        array $splitMatch,
        bool $isPpv,
    ): ParsedWikipediaMatch {
        $participantsRaw = trim(substr($matchLine, 0, $splitMatch[0][1]));

        if (! preg_match('/\s+vs\.?\s+/i', $participantsRaw, $versusMatch, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("Could not parse participants for no contest match {$cardOrder}.");
        }

        $sideOneRaw = trim(substr($participantsRaw, 0, $versusMatch[0][1]));
        $sideTwoRaw = trim(substr($participantsRaw, $versusMatch[0][1] + strlen($versusMatch[0][0])));

        $participants = array_merge(
            $this->parseTeamsFromSide($sideOneRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($sideTwoRaw, 2, combineSegments: true),
        );

        $cleanStipulation = $this->stripWikiMarkup($stipulation);

        return new ParsedWikipediaMatch(
            cardOrder: $cardOrder,
            matchType: $this->resolveMatchType($cleanStipulation, 'no_contest'),
            titleName: $this->extractTitleName($cleanStipulation),
            participants: $participants,
            winnerSide: null,
            finish: 'no_contest',
            durationSeconds: $this->parseDuration($time),
            isRateable: ! str_contains(strtolower($cleanStipulation), 'dark match'),
            isPpv: $isPpv,
        );
    }

    /**
     * @return list<array{name: string, side: int, sort_order: int}>
     */
    private function parseTeamsFromSide(string $raw, int $startingSide, bool $combineSegments): array
    {
        $segments = $this->extractTeamSegments($this->removeManagerClauses($raw));

        if ($segments === []) {
            return [];
        }

        $formattedTeams = array_map(
            fn (string $segment): string => $this->formatTeamSegment($segment),
            $segments,
        );

        if ($combineSegments) {
            return [[
                'name' => implode(' & ', $formattedTeams),
                'side' => $startingSide,
                'sort_order' => 0,
            ]];
        }

        $participants = [];
        $side = $startingSide;

        foreach ($formattedTeams as $teamName) {
            $participants[] = [
                'name' => $teamName,
                'side' => $side++,
                'sort_order' => 0,
            ];
        }

        return $participants;
    }

    /**
     * @return list<string>
     */
    private function extractTeamSegments(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $segments = [];
        $current = '';
        $depth = 0;
        $length = strlen($text);

        for ($index = 0; $index < $length; $index++) {
            $character = $text[$index];

            if ($character === '(') {
                $depth++;
                $current .= $character;

                continue;
            }

            if ($character === ')') {
                $depth--;
                $current .= $character;

                continue;
            }

            if ($depth === 0) {
                $delimiterLength = $this->segmentDelimiterLength($text, $index);

                if ($delimiterLength !== null) {
                    if (trim($current) !== '') {
                        $segments[] = trim($current);
                    }

                    $current = '';
                    $index += $delimiterLength - 1;

                    continue;
                }
            }

            $current .= $character;
        }

        if (trim($current) !== '') {
            $segments[] = trim($current);
        }

        return $segments;
    }

    private function formatTeamSegment(string $segment): string
    {
        $segment = preg_replace('/\s*\(c\)/i', '', $segment) ?? $segment;
        $segment = preg_replace('/\s+by\s+.+$/i', '', $segment) ?? $segment;
        $segment = trim($segment);

        if (preg_match('/^\[\[(?:[^|\]]+\|)?([^\]]+)\]\]\s*\((.+)\)$/s', $segment, $matches) === 1) {
            $teamName = trim($matches[1]);

            if (str_contains($matches[2], '[[')) {
                $memberNames = $this->extractMemberNames($matches[2]);

                if ($memberNames !== []) {
                    return "{$teamName} (".implode(' & ', $memberNames).')';
                }
            }

            return $teamName;
        }

        if (preg_match('/^\[\[(?:[^|\]]+\|)?([^\]]+)\]\]$/', $segment, $matches) === 1) {
            return $this->stripWikiMarkup(trim($matches[1]));
        }

        return $this->stripWikiMarkup($segment);
    }

    /**
     * @return list<string>
     */
    private function extractMemberNames(string $clause): array
    {
        preg_match_all('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', $clause, $linkMatches);

        if ($linkMatches[1] !== []) {
            return array_values(array_unique(array_map('trim', $linkMatches[1])));
        }

        return [];
    }

    /**
     * @param  list<array{name: string, side: int, sort_order: int}>  $participants
     */
    /**
     * @param  list<string>  $entrantNames
     */
    private function buildParsedMatch(
        int $cardOrder,
        string $stipulation,
        string $time,
        array $participants,
        string $finish,
        bool $isPpv = true,
        ?int $winnerSide = 1,
        array $entrantNames = [],
    ): ParsedWikipediaMatch {
        $cleanStipulation = $this->stripWikiMarkup($stipulation);

        return new ParsedWikipediaMatch(
            cardOrder: $cardOrder,
            matchType: $this->resolveMatchType($cleanStipulation, $finish),
            titleName: $this->extractTitleName($cleanStipulation),
            participants: $participants,
            winnerSide: $winnerSide,
            finish: $finish,
            durationSeconds: $this->parseDuration($time),
            isRateable: ! str_contains(strtolower($cleanStipulation), 'dark match'),
            isPpv: $isPpv,
            entrantNames: $entrantNames,
        );
    }

    /**
     * @return list<string>
     */
    private function extractBattleRoyalEntrantNames(string $matchLine): array
    {
        if (preg_match('/<ref[^>]*>(.*?)<\/ref>/is', $matchLine, $refMatch) !== 1) {
            return [];
        }

        $names = $this->extractMemberNames($refMatch[1]);

        return array_values(array_filter(
            $names,
            static fn (string $name): bool => $name !== '',
        ));
    }

    private function resolveIsPpv(string $note): bool
    {
        return ! in_array($note, ['dark', 'wcwme', 'heat', 'ffa'], true);
    }

    /**
     * @return list<ParsedWikipediaMatch>
     */
    private function parseWikitable(string $section): array
    {
        if (preg_match('/\{\|.*?\|\}/is', $section, $tableMatch) !== 1) {
            throw new RuntimeException('Results section does not contain a recognizable results table.');
        }

        $rows = preg_split('/\n\|-/', $tableMatch[0]) ?: [];
        $matches = [];
        $cardOrder = 0;

        foreach ($rows as $row) {
            if (! str_contains($row, '|') || str_contains(strtolower($row), '! number') || str_contains(strtolower($row), '! results')) {
                continue;
            }

            $cells = array_values(array_filter(array_map('trim', preg_split('/\n\|/', $row) ?: [])));

            if (count($cells) < 3) {
                continue;
            }

            $rawNumber = rtrim($cells[0], '!');
            $cardNumber = preg_replace('/[^0-9]/', '', $rawNumber);

            if ($cardNumber === '') {
                continue;
            }

            $cardOrder = (int) $cardNumber;
            $resultCell = $cells[1] ?? '';
            $stipulation = $cells[2] ?? '';
            $time = $cells[3] ?? '';
            $isPpv = preg_match('/(?:D|ME|H|F)$/i', $rawNumber) !== 1;

            $cleanResult = strtolower($this->stripWikiMarkup($resultCell));

            if (
                preg_match('/\s+defeated\s+/i', $cleanResult) !== 1
                && ! str_contains($cleanResult, 'ended in a no contest')
                && ! str_contains($cleanResult, 'won by last eliminating')
            ) {
                continue;
            }

            $matches[] = $this->parseMatchLine($resultCell, $stipulation, $time, $cardOrder, $isPpv);
        }

        if ($matches === []) {
            throw new RuntimeException('Wikitable results section contained no parseable matches.');
        }

        return $matches;
    }

    private function stripWikiMarkup(string $value): string
    {
        $value = $this->removeReferenceTags($value);
        $value = preg_replace('/\{\{[^}]+\}\}/', '', $value) ?? $value;
        $value = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', '$1', $value) ?? $value;
        $value = preg_replace("/'''+/", '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function removeReferenceTags(string $value): string
    {
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*\/>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*?<\/ref>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*/is', '', $value) ?? $value;

        return trim($value);
    }

    private function removeManagerClauses(string $text): string
    {
        $text = preg_replace(
            '/\(with (?:\[\[[^\]]+\]\](?:\s+and\s+\[\[[^\]]+\]\])*)\)/i',
            '',
            $text,
        ) ?? $text;

        return preg_replace('/\(with [^)]+\)/i', '', $text) ?? $text;
    }

    private function isTriangleMatch(string $stipulation): bool
    {
        return str_contains(strtolower($this->stripWikiMarkup($stipulation)), 'triangle');
    }

    private function segmentDelimiterLength(string $text, int $index): ?int
    {
        if (strcasecmp(substr($text, $index, 6), ', and ') === 0) {
            return 6;
        }

        if (strcasecmp(substr($text, $index, 5), ' and ') === 0) {
            return 5;
        }

        if (($text[$index] ?? '') === ',' && ($text[$index + 1] ?? '') === ' ') {
            return 2;
        }

        return null;
    }

    private function resolveMatchType(string $stipulation, string $finish): string
    {
        if ($finish === 'last_elimination') {
            return 'battle_royal';
        }

        if ($finish === 'no_contest') {
            return 'tag';
        }

        $lower = strtolower($stipulation);

        if (str_contains($lower, 'war games') || str_contains($lower, 'wargames')) {
            return 'war_games';
        }

        if (str_contains($lower, 'battle royal') || str_contains($lower, 'world war 3') || str_contains($lower, 'royal rumble')) {
            return 'battle_royal';
        }

        if (str_contains($lower, 'triangle')) {
            return 'triple_threat';
        }

        if (str_contains($lower, 'tag team') || str_contains($lower, 'six-man')) {
            return 'tag';
        }

        if (str_contains($lower, 'no disqualification')) {
            return 'no_disqualification';
        }

        if (str_contains($lower, 'singles')) {
            return 'singles';
        }

        return 'singles';
    }

    private function extractTitleName(string $stipulation): ?string
    {
        if (preg_match('/for a future (.+?) match/i', $stipulation, $matches) === 1) {
            return trim($this->stripWikiMarkup($matches[1]));
        }

        if (preg_match('/for the (?:vacant |inaugural )?(.+?)(?:\.|$)/i', $stipulation, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function parseDuration(string $time): ?int
    {
        $time = $this->stripWikiMarkup($time);

        if ($time === '' || ! str_contains($time, ':')) {
            return null;
        }

        $parts = explode(':', $time);

        if (count($parts) !== 2) {
            return null;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }
}
