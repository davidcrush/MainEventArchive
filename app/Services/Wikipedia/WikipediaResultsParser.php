<?php

namespace App\Services\Wikipedia;

use App\Data\ParsedWikipediaMatch;
use App\Exceptions\WikipediaMatchCountMismatchException;
use App\Services\Wikipedia\Concerns\BuildsMatchParticipants;
use RuntimeException;

class WikipediaResultsParser
{
    use BuildsMatchParticipants;

    public function __construct(
        private readonly WikipediaEventSectionExtractor $sectionExtractor,
    ) {}

    /**
     * @return list<ParsedWikipediaMatch>
     */
    public function parse(string $wikitext, string ...$eventScopeHeadings): array
    {
        $content = $this->resolveResultsContent($wikitext, ...$eventScopeHeadings);
        $resultsSection = $this->extractResultsSection($content);

        if ($resultsSection === null) {
            if ($eventScopeHeadings !== [] && preg_match('/\{\{Pro [Ww]restling results table/i', $content) === 1) {
                $resultsSection = $content;
            } else {
                throw new RuntimeException('No Results section found on Wikipedia page.');
            }
        }

        if (preg_match('/\{\{Pro [Ww]restling results table/i', $resultsSection) === 1) {
            $matches = $this->parseProWrestlingResultsTemplate($resultsSection);
            $this->assertParsedMatchCount($resultsSection, $matches, isTemplate: true);

            return $matches;
        }

        $matches = $this->parseWikitable($resultsSection);
        $this->assertParsedMatchCount($resultsSection, $matches, isTemplate: false);

        return $matches;
    }

    /**
     * Count non-empty match slots declared on a Wikipedia results table.
     */
    public function countDeclaredMatches(string $wikitext, string ...$eventScopeHeadings): int
    {
        $content = $this->resolveResultsContent($wikitext, ...$eventScopeHeadings);
        $resultsSection = $this->extractResultsSection($content);

        if ($resultsSection === null) {
            if ($eventScopeHeadings !== [] && preg_match('/\{\{Pro [Ww]restling results table/i', $content) === 1) {
                $resultsSection = $content;
            } else {
                throw new RuntimeException('No Results section found on Wikipedia page.');
            }
        }

        if (preg_match('/\{\{Pro [Ww]restling results table/i', $resultsSection) === 1) {
            return $this->countDeclaredTemplateMatches($resultsSection);
        }

        return $this->countDeclaredWikitableMatches($resultsSection);
    }

    private function resolveResultsContent(string $wikitext, string ...$eventScopeHeadings): string
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

        return $content;
    }

    /**
     * @param  list<ParsedWikipediaMatch>  $matches
     */
    private function assertParsedMatchCount(string $resultsSection, array $matches, bool $isTemplate): void
    {
        $declaredCount = $isTemplate
            ? $this->countDeclaredTemplateMatches($resultsSection)
            : $this->countDeclaredWikitableMatches($resultsSection);

        $parsedCount = count($matches);

        if ($declaredCount !== $parsedCount) {
            throw new WikipediaMatchCountMismatchException($declaredCount, $parsedCount);
        }
    }

    private function countDeclaredTemplateMatches(string $section): int
    {
        $body = $this->extractProWrestlingResultsTemplateBody($section);
        $parameters = $this->parseTemplateParameters($body);
        $count = 0;

        foreach ($parameters as $key => $value) {
            if (preg_match('/^match(\d+)$/', $key) === 1 && trim($value) !== '') {
                $count++;
            }
        }

        return $count;
    }

    private function countDeclaredWikitableMatches(string $section): int
    {
        if (preg_match('/\{\|.*?\|\}/is', $section, $tableMatch) !== 1) {
            throw new RuntimeException('Results section does not contain a recognizable results table.');
        }

        $rows = preg_split('/\n\|-/', $tableMatch[0]) ?: [];
        $count = 0;

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

            $count++;
        }

        return $count;
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
        $body = $this->extractProWrestlingResultsTemplateBody($section);
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
     * Extract the inner body of {{Pro Wrestling results table ...}} while tolerating
     * nested templates (e.g. {{cite web}} in |results) and closing }} on the same
     * line as the final parameter.
     */
    private function extractProWrestlingResultsTemplateBody(string $section): string
    {
        if (preg_match('/\{\{Pro [Ww]restling results table/is', $section, $startMatch, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException('Results section does not contain a Pro Wrestling results table.');
        }

        $start = $startMatch[0][1] + strlen($startMatch[0][0]);
        $depth = 2;
        $length = strlen($section);
        $body = '';

        for ($index = $start; $index < $length; $index++) {
            if ($index < $length - 1 && $section[$index] === '{' && $section[$index + 1] === '{') {
                $depth += 2;
                $body .= '{{';
                $index++;

                continue;
            }

            if ($index < $length - 1 && $section[$index] === '}' && $section[$index + 1] === '}') {
                $depth -= 2;

                if ($depth === 0) {
                    return $body;
                }

                $body .= '}}';
                $index++;

                continue;
            }

            $body .= $section[$index];
        }

        throw new RuntimeException('Results section does not contain a Pro Wrestling results table.');
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

        if (preg_match('/\s+won\s+by\s+last\s+defeating\s+/i', $matchLine) === 1) {
            $entrantNames = $this->extractBattleRoyalEntrantNames($matchLine);
        }

        if (preg_match('/\s+won\s+when\s+.+\s+eliminated\s+each\s+other/i', $matchLine) === 1) {
            $entrantNames = $this->extractBattleRoyalEntrantNames($matchLine);
        }

        $matchLine = $this->removeReferenceTags($matchLine);

        if (preg_match('/\s+defeated\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseStandardMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv);
        }

        if (preg_match('/\s+won\s+by\s+(?:"last eliminating"|last eliminating)\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseLastEliminationMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv, $entrantNames);
        }

        if (preg_match('/\s+won\s+by\s+last\s+defeating\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseLastEliminationMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv, $entrantNames);
        }

        if (preg_match('/\s+won\s+when\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseWonWhenEliminatedEachOtherMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv, $entrantNames);
        }

        if (preg_match('/\s+ended\s+in\s+a\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseNoContestMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv);
        }

        if (preg_match('/\s+ended\s+when\s+/i', $matchLine, $splitMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseEndedWhenMatchLine($matchLine, $stipulation, $time, $cardOrder, $splitMatch, $isPpv);
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

        $titleName = $this->extractTitleName($this->stripWikiMarkup($stipulation));
        $championSide = $this->detectChampionSide($winnerRaw, $loserAndFinishRaw);

        ['participants' => $participants, 'winnerSide' => $winnerSide] = $this->applySpoilerSafeSideOrder(
            $participants,
            $cardOrder,
            $titleName,
            $championSide,
        );

        return $this->buildParsedMatch(
            cardOrder: $cardOrder,
            stipulation: $stipulation,
            time: $time,
            participants: $participants,
            finish: $finish ?? 'pinfall',
            isPpv: $isPpv,
            winnerSide: $winnerSide,
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
     * @param  list<string>  $entrantNames
     */
    private function parseWonWhenEliminatedEachOtherMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        array $splitMatch,
        bool $isPpv,
        array $entrantNames = [],
    ): ParsedWikipediaMatch {
        $winnersRaw = trim(substr($matchLine, 0, $splitMatch[0][1]));
        $losersRaw = trim(substr($matchLine, $splitMatch[0][1] + strlen($splitMatch[0][0])));
        $losersRaw = preg_replace('/\s+eliminated\s+each\s+other.*$/i', '', $losersRaw) ?? $losersRaw;

        $participants = array_merge(
            $this->parseTeamsFromSide($winnersRaw, 1, combineSegments: false),
            $this->parseTeamsFromSide($losersRaw, 2, combineSegments: false),
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
     * @param  array<int, array{0: non-empty-string, 1: int}>  $splitMatch
     */
    private function parseEndedWhenMatchLine(
        string $matchLine,
        string $stipulation,
        string $time,
        int $cardOrder,
        array $splitMatch,
        bool $isPpv,
    ): ParsedWikipediaMatch {
        $participantsRaw = trim(substr($matchLine, 0, $splitMatch[0][1]));

        if (! preg_match('/\s+vs\.?\s+/i', $participantsRaw, $versusMatch, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("Could not parse participants for ended-when match {$cardOrder}.");
        }

        $sideOneRaw = trim(substr($participantsRaw, 0, $versusMatch[0][1]));
        $sideTwoRaw = trim(substr($participantsRaw, $versusMatch[0][1] + strlen($versusMatch[0][0])));

        $participants = array_merge(
            $this->parseTeamsFromSide($sideOneRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($sideTwoRaw, 2, combineSegments: true),
        );

        $cleanStipulation = $this->stripWikiMarkup($stipulation);
        $finishRaw = trim(substr($matchLine, $splitMatch[0][1] + strlen($splitMatch[0][0])));
        $finish = $this->stripWikiMarkup($finishRaw);

        return new ParsedWikipediaMatch(
            cardOrder: $cardOrder,
            matchType: $this->resolveMatchType($cleanStipulation, 'no_contest'),
            titleName: $this->extractTitleName($cleanStipulation),
            participants: $participants,
            winnerSide: null,
            finish: $finish !== '' ? $finish : 'no_contest',
            durationSeconds: $this->parseDuration($time),
            isRateable: ! str_contains(strtolower($cleanStipulation), 'dark match'),
            isPpv: $isPpv,
        );
    }

    /**
     * @param  list<array{name: string, side: int, sort_order: int}>  $participants
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
}
