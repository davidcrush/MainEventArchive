<?php

namespace App\Services\Fandom;

use App\Data\ParsedWikipediaMatch;
use App\Exceptions\WikipediaMatchCountMismatchException;
use App\Services\Wikipedia\Concerns\BuildsMatchParticipants;
use RuntimeException;

/**
 * Parses the bullet-list match results found on prowrestling.fandom.com episode
 * pages (e.g. "January 1, 1996 Monday Nitro results"). The side-content grammar
 * matches our Wikipedia parser, so participant building and spoiler-safe
 * ordering are shared via {@see BuildsMatchParticipants}.
 *
 * Line grammar (under `==Results==`):
 *   *[ [[Dark Match]]: ] <sideA> defeated <sideB> [by <finish>]
 *       [to win/retain the [[Title]] | in a [[Title]]/<stip> Match] (M:SS)
 *   *<sideA> (c) vs. <sideB> ended in a <result> [in a [[Title]] Match] (M:SS)
 *   *'''Match Type:''' <sideA> defeated <sideB> ... | <sideA> vs. <sideB> - No Contest (M:SS)
 *   *'''Match Type:'''
 *   :<sideA> vs. <sideB>
 *   :*'''Winner:''' <winner> [via <finish>] (M:SS)
 */
class FandomNitroResultsParser
{
    use BuildsMatchParticipants;

    /**
     * @return list<ParsedWikipediaMatch>
     */
    public function parse(string $wikitext): array
    {
        if (! $this->hasResultsSection($wikitext)) {
            throw new RuntimeException('No Results section found on Fandom page.');
        }

        $bullets = array_values(array_filter(
            $this->extractResultBullets($wikitext),
            fn (string $bullet): bool => $this->isParseableResultBullet($bullet),
        ));

        if ($bullets === []) {
            return [];
        }

        $declaredCount = count($bullets);
        $matches = [];
        $cardOrder = 0;

        foreach ($bullets as $bullet) {
            $cardOrder++;

            try {
                $matches[] = $this->parseBullet($bullet, $cardOrder);
            } catch (RuntimeException) {
                continue;
            }
        }

        if (count($matches) !== $declaredCount) {
            throw new WikipediaMatchCountMismatchException($declaredCount, count($matches));
        }

        return $matches;
    }

    public function countDeclaredMatches(string $wikitext): int
    {
        if (! $this->hasResultsSection($wikitext)) {
            return 0;
        }

        return count(array_filter(
            $this->extractResultBullets($wikitext),
            fn (string $bullet): bool => $this->isParseableResultBullet($bullet),
        ));
    }

    private function hasResultsSection(string $wikitext): bool
    {
        return preg_match('/==\s*Results?\s*==/i', $wikitext) === 1;
    }

    private function isParseableResultBullet(string $bullet): bool
    {
        if (preg_match('/\s+(?:defeated|def\.)\s+/i', $bullet) === 1) {
            return true;
        }

        if (preg_match('/\s+ended\s+in\s+/i', $bullet) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function extractResultBullets(string $wikitext): array
    {
        $lines = $this->extractResultLines($wikitext);

        if ($lines === []) {
            return [];
        }

        $bullets = [];
        $lineCount = count($lines);

        for ($index = 0; $index < $lineCount; $index++) {
            $trimmed = trim($lines[$index]);

            if (! $this->isTopLevelBulletLine($trimmed)) {
                continue;
            }

            $content = trim(ltrim($trimmed, '*'));

            if ($content === '') {
                continue;
            }

            if ($this->isMatchTypeHeaderOnly($content) && ($lines[$index + 1] ?? null) !== null) {
                $participantsLine = trim($lines[$index + 1]);

                if (str_starts_with($participantsLine, ':') && ! str_starts_with($participantsLine, ':*')) {
                    $participants = trim(ltrim($participantsLine, ':'));
                    $winnerLine = null;
                    $lookahead = $index + 2;

                    if (($lines[$lookahead] ?? null) !== null && str_starts_with(trim($lines[$lookahead]), ':*')) {
                        $winnerCandidate = trim(ltrim(ltrim(trim($lines[$lookahead]), ':'), '*'));

                        if (preg_match("/^'''Winner:'''/i", $winnerCandidate) === 1) {
                            $winnerLine = $winnerCandidate;
                            $index = $lookahead;
                        }
                    }

                    if ($winnerLine !== null) {
                        $bullets[] = $this->normalizeWinnerFormatBlock($content, $participants, $winnerLine);

                        continue;
                    }
                }
            }

            $bullets[] = $this->normalizeInlineBullet($content);
        }

        return $bullets;
    }

    /**
     * @return list<string>
     */
    private function extractResultLines(string $wikitext): array
    {
        if (preg_match('/==\s*Results?\s*==(.+?)(?=\n==[^=]|$)/is', $wikitext, $sectionMatch) !== 1) {
            return [];
        }

        return preg_split('/\R/', $sectionMatch[1]) ?: [];
    }

    private function isTopLevelBulletLine(string $line): bool
    {
        return $line !== ''
            && str_starts_with($line, '*')
            && ! str_starts_with($line, '**:')
            && ! str_starts_with($line, '*:');
    }

    private function isMatchTypeHeaderOnly(string $content): bool
    {
        if (preg_match('/\s+(?:defeated|def\.)\s+/i', $content) === 1) {
            return false;
        }

        if (preg_match('/\s+vs\.?\s*/i', $content) === 1) {
            return false;
        }

        if (preg_match('/\s+ended\s+in\s+/i', $content) === 1) {
            return false;
        }

        $stripped = preg_replace("/'''/", '', $content) ?? $content;

        return str_ends_with(rtrim($stripped), ':');
    }

    private function normalizeWinnerFormatBlock(string $header, string $participants, ?string $winnerLine): string
    {
        if ($winnerLine === null || $winnerLine === '') {
            throw new RuntimeException('Winner line missing for multi-line Nitro match.');
        }

        [, $stipPrefix] = $this->splitBoldMatchTypePrefix($header);

        $winnerLine = rtrim($this->removeReferenceTags($winnerLine), '.');
        $winnerLine = preg_replace("/^'''Winner:'''\s*/i", '', $winnerLine) ?? $winnerLine;

        $time = '';

        if (preg_match('/\((\d{1,2}:\d{2})\)\s*$/', $winnerLine, $timeMatch) === 1) {
            $time = $timeMatch[1];
            $winnerLine = trim(substr($winnerLine, 0, -strlen($timeMatch[0])));
        }

        $finish = null;

        if (preg_match('/\s+via\s+(.+)$/i', $winnerLine, $finishMatch) === 1) {
            $finish = trim($finishMatch[1]);
            $winnerLine = trim(substr($winnerLine, 0, -strlen($finishMatch[0])));
        }

        if (preg_match('/\s+vs\.?\s*/i', $participants, $versusMatch, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException('Could not parse participants for multi-line Nitro match.');
        }

        $sideOne = trim(substr($participants, 0, $versusMatch[0][1]));
        $sideTwo = trim(substr($participants, $versusMatch[0][1] + strlen($versusMatch[0][0])));
        $winnerNames = trim($winnerLine);

        if ($this->sideContainsWinnerNames($sideOne, $winnerNames)) {
            $line = "{$sideOne} defeated {$sideTwo}";
        } elseif ($this->sideContainsWinnerNames($sideTwo, $winnerNames)) {
            $line = "{$sideTwo} defeated {$sideOne}";
        } else {
            throw new RuntimeException('Could not match winner to participants for multi-line Nitro match.');
        }

        if ($finish !== null && $finish !== '') {
            $line .= " by {$finish}";
        }

        if ($stipPrefix !== null) {
            $line .= " in a {$stipPrefix}";
        }

        if ($time !== '') {
            $line .= " ({$time})";
        }

        return $line;
    }

    private function normalizeInlineBullet(string $content): string
    {
        [$line, $stipPrefix] = $this->splitBoldMatchTypePrefix($content);

        $line = $this->stripChampionRoleLinks($line);
        $line = preg_replace('/\s+fought\s+/i', ' vs. ', $line) ?? $line;
        $line = preg_replace('/\s+to\s+a\s+no\s+contest/i', ' ended in a No Contest', $line) ?? $line;
        $line = preg_replace(
            '/\s+-\s+(Time Limit Draw|No Contest|Double Count-?out|Double DQ|Double Disqualification)(\s*\(\d{1,2}:\d{2}\))?\s*$/i',
            ' ended in a $1$2',
            $line,
        ) ?? $line;

        if ($stipPrefix !== null && ! preg_match('/\s+in\s+(?:a|an)\s+.+\s+match\s*$/i', $line)) {
            $line .= " in a {$stipPrefix}";
        }

        return $line;
    }

    private function stripChampionRoleLinks(string $line): string
    {
        return preg_replace(
            '/\[\[(?:[^\]|]+\|)?[^\]]*\bChampion\b[^\]]*\]\]\s*/i',
            '',
            $line,
        ) ?? $line;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitBoldMatchTypePrefix(string $line): array
    {
        if (preg_match("/^'''(.+?)'''\s*(.*)$/s", $line, $match) !== 1) {
            return [$line, null];
        }

        $prefix = trim(rtrim($match[1], ':'));

        return [trim($match[2]), $prefix !== '' ? $prefix : null];
    }

    private function sideContainsWinnerNames(string $sideRaw, string $winnerNames): bool
    {
        $side = strtolower($this->stripWikiMarkup($sideRaw));
        $winner = strtolower($this->stripWikiMarkup($winnerNames));

        if ($side === $winner) {
            return true;
        }

        $winnerParts = array_map('trim', preg_split('/\s*&\s*/', $winner) ?: []);

        foreach ($winnerParts as $part) {
            if ($part !== '' && ! str_contains($side, $part)) {
                return false;
            }
        }

        return $winnerParts !== [];
    }

    private function parseBullet(string $line, int $cardOrder): ParsedWikipediaMatch
    {
        $line = $this->removeReferenceTags($line);

        $isDark = false;

        if (preg_match('/^(?:\[\[\s*dark match\s*\]\]|dark match)\s*:\s*/i', $line) === 1) {
            $isDark = true;
            $line = preg_replace('/^(?:\[\[\s*dark match\s*\]\]|dark match)\s*:\s*/i', '', $line) ?? $line;
        }

        $time = '';
        $line = $this->extractTrailingMatchTime($line, $time);

        if (preg_match('/\s+(?:defeated|def\.)\s+/i', $line, $verbMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseDecisiveLine($line, $verbMatch, $cardOrder, $time, $isDark);
        }

        if (preg_match('/\s+ended\s+in\s+/i', $line) === 1) {
            return $this->parseDrawLine($line, $cardOrder, $time, $isDark);
        }

        throw new RuntimeException("Could not parse Nitro match {$cardOrder}.");
    }

    private function extractTrailingMatchTime(string $line, ?string &$time): string
    {
        $time = '';

        if (preg_match('/\s+in\s+(?:a|an)\s+.+\s+match\s*$/i', $line, $stipSuffix, PREG_OFFSET_CAPTURE) === 1) {
            $lineWithoutStip = trim(substr($line, 0, $stipSuffix[0][1]));
            $trailingStip = $stipSuffix[0][0];
        } else {
            $lineWithoutStip = $line;
            $trailingStip = '';
        }

        if (preg_match('/\((\d{1,2}:\d{2})\)\s*$/', $lineWithoutStip, $timeMatch) === 1) {
            $time = $timeMatch[1];
            $lineWithoutStip = trim(substr($lineWithoutStip, 0, -strlen($timeMatch[0])));
        }

        return $lineWithoutStip.$trailingStip;
    }

    /**
     * @param  array<int, array{0: non-empty-string, 1: int}>  $verbMatch
     */
    private function parseDecisiveLine(string $line, array $verbMatch, int $cardOrder, string $time, bool $isDark): ParsedWikipediaMatch
    {
        $winnerRaw = trim(substr($line, 0, $verbMatch[0][1]));
        $remainder = trim(substr($line, $verbMatch[0][1] + strlen($verbMatch[0][0])));

        [$remainder, $titleName, $stipText] = $this->peelTitleAndStipulation($remainder);

        $finish = null;

        if (preg_match('/\s+by\s+(.+?)\s*$/i', $remainder, $finishMatch) === 1) {
            $finish = $this->normalizeFinish($this->stripWikiMarkup($finishMatch[1]));
            $remainder = trim(substr($remainder, 0, -strlen($finishMatch[0])));
        }

        $remainder = preg_replace('/\s+at around\b.*/i', '', $remainder) ?? $remainder;
        $remainder = preg_replace('/\s+after fighting off\b.*/i', '', $remainder) ?? $remainder;

        $loserRaw = $remainder;
        $championSide = $this->detectChampionSide($winnerRaw, $loserRaw);
        $splitLoserIntoSides = $stipText !== '' && $this->isTriangleMatch($stipText);

        $participants = array_merge(
            $this->parseTeamsFromSide($winnerRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($loserRaw, 2, combineSegments: ! $splitLoserIntoSides),
        );

        if ($participants === []) {
            throw new RuntimeException("Could not parse participants for Nitro match {$cardOrder}.");
        }

        ['participants' => $participants, 'winnerSide' => $winnerSide] = $this->applySpoilerSafeSideOrder(
            $participants,
            $cardOrder,
            $titleName,
            $championSide,
        );

        return $this->buildNitroMatch(
            cardOrder: $cardOrder,
            participants: $participants,
            winnerSide: $winnerSide,
            finish: $finish ?? 'pinfall',
            time: $time,
            titleName: $titleName,
            stipText: $stipText,
            isDark: $isDark,
        );
    }

    private function parseDrawLine(string $line, int $cardOrder, string $time, bool $isDark): ParsedWikipediaMatch
    {
        if (preg_match('/\s+ended\s+in\s+/i', $line, $endedMatch, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException("Could not parse Nitro draw {$cardOrder}.");
        }

        $participantsRaw = trim(substr($line, 0, $endedMatch[0][1]));
        $resultTail = trim(substr($line, $endedMatch[0][1] + strlen($endedMatch[0][0])));

        [$resultTail, $titleName, $stipText] = $this->peelTitleAndStipulation($resultTail);
        $finish = $this->normalizeDrawFinish($this->stripWikiMarkup($resultTail));

        if (preg_match('/\s+vs\.?\s*/i', $participantsRaw, $versusMatch, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException("Could not parse participants for Nitro draw {$cardOrder}.");
        }

        $sideOneRaw = trim(substr($participantsRaw, 0, $versusMatch[0][1]));
        $sideTwoRaw = trim(substr($participantsRaw, $versusMatch[0][1] + strlen($versusMatch[0][0])));

        $championSide = $this->detectChampionSide($sideOneRaw, $sideTwoRaw);

        $participants = array_merge(
            $this->parseTeamsFromSide($sideOneRaw, 1, combineSegments: true),
            $this->parseTeamsFromSide($sideTwoRaw, 2, combineSegments: true),
        );

        if ($participants === []) {
            throw new RuntimeException("Could not parse participants for Nitro draw {$cardOrder}.");
        }

        ['participants' => $participants] = $this->applySpoilerSafeSideOrder(
            $participants,
            $cardOrder,
            $titleName,
            $championSide,
        );

        return $this->buildNitroMatch(
            cardOrder: $cardOrder,
            participants: $participants,
            winnerSide: null,
            finish: $finish,
            time: $time,
            titleName: $titleName,
            stipText: $stipText,
            isDark: $isDark,
        );
    }

    /**
     * Strip trailing title/stipulation clauses ("to win the [[Title]]" or
     * "in a [[Title]]/<stip> Match") from a line, returning the cleaned line,
     * the resolved title name (championships only), and the stipulation text.
     *
     * @return array{0: string, 1: ?string, 2: string}
     */
    private function peelTitleAndStipulation(string $text): array
    {
        $titleName = null;
        $stipText = '';

        if (preg_match('/\s+to\s+(?:win|retain|capture|regain|unify|become)\b.*$/i', $text, $titleMatch, PREG_OFFSET_CAPTURE) === 1) {
            if (preg_match('/\[\[([^\]]+)\]\]/', $titleMatch[0][0], $linkMatch) === 1) {
                $titleName = $this->linkLabel($linkMatch[1]);
            }

            $text = trim(substr($text, 0, $titleMatch[0][1]));
        }

        if (preg_match('/\s+in\s+(?:a|an)\s+(.+?)\s+match\s*$/i', $text, $stipMatch, PREG_OFFSET_CAPTURE) === 1) {
            $captured = $stipMatch[1][0];
            $stipText = $this->stripWikiMarkup($captured);

            if ($titleName === null && preg_match('/\[\[([^\]]+)\]\]/', $captured, $stipLink) === 1) {
                $label = $this->linkLabel($stipLink[1]);

                if (stripos($label, 'championship') !== false || stripos($label, 'title') !== false) {
                    $titleName = $label;
                }
            }

            if ($titleName === null && (stripos($stipText, 'championship') !== false || stripos($stipText, 'title') !== false)) {
                $titleName = $stipText;
            }

            $text = trim(substr($text, 0, $stipMatch[0][1]));
        }

        return [$text, $titleName, $stipText];
    }

    private function buildNitroMatch(
        int $cardOrder,
        array $participants,
        ?int $winnerSide,
        ?string $finish,
        string $time,
        ?string $titleName,
        string $stipText,
        bool $isDark,
    ): ParsedWikipediaMatch {
        return new ParsedWikipediaMatch(
            cardOrder: $cardOrder,
            matchType: $this->resolveNitroMatchType($stipText, $participants),
            titleName: $titleName,
            participants: $participants,
            winnerSide: $winnerSide,
            finish: $finish,
            durationSeconds: $this->parseDuration($time),
            isRateable: ! $isDark,
            isPpv: ! $isDark,
        );
    }

    /**
     * @param  list<array{name: string, side: int, sort_order: int}>  $participants
     */
    private function resolveNitroMatchType(string $stipText, array $participants): string
    {
        if ($stipText !== '') {
            $keywordType = $this->resolveMatchType($stipText, 'decisive');

            if ($keywordType !== 'singles') {
                return $keywordType;
            }
        }

        foreach ($participants as $participant) {
            if (str_contains($participant['name'], ' & ')) {
                return 'tag';
            }
        }

        $sides = array_unique(array_map(static fn (array $participant): int => $participant['side'], $participants));

        if (count($sides) >= 3) {
            return 'triple_threat';
        }

        return 'singles';
    }

    private function normalizeFinish(string $finish): string
    {
        $lower = strtolower(trim($finish));

        return match (true) {
            $lower === '' || str_contains($lower, 'pinfall') || $lower === 'pin' => 'pinfall',
            $lower === 'dq' || str_contains($lower, 'disqualif') => 'disqualification',
            str_contains($lower, 'count') => 'countout',
            str_contains($lower, 'submission') || str_contains($lower, 'tap') => 'submission',
            str_contains($lower, 'forfeit') => 'forfeit',
            str_contains($lower, 'knockout') || $lower === 'ko' => 'knockout',
            default => $finish,
        };
    }

    private function normalizeDrawFinish(string $result): string
    {
        $lower = strtolower($result);

        return match (true) {
            str_contains($lower, 'time limit') => 'time_limit_draw',
            str_contains($lower, 'double count') => 'double_countout',
            str_contains($lower, 'double dq') || str_contains($lower, 'double disqualif') => 'double_disqualification',
            str_contains($lower, 'no contest') => 'no_contest',
            str_contains($lower, 'draw') => 'draw',
            default => $result !== '' ? $result : 'no_contest',
        };
    }

    private function linkLabel(string $link): string
    {
        if (str_contains($link, '|')) {
            return trim(explode('|', $link, 2)[1]);
        }

        return trim($link);
    }
}
