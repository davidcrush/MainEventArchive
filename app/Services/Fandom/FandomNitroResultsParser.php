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
 */
class FandomNitroResultsParser
{
    use BuildsMatchParticipants;

    /**
     * @return list<ParsedWikipediaMatch>
     */
    public function parse(string $wikitext): array
    {
        $bullets = $this->extractResultBullets($wikitext);

        if ($bullets === []) {
            throw new RuntimeException('No Results section found on Fandom page.');
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
        return count($this->extractResultBullets($wikitext));
    }

    /**
     * @return list<string>
     */
    private function extractResultBullets(string $wikitext): array
    {
        if (preg_match('/==\s*Results\s*==(.+?)(?=\n==[^=]|$)/is', $wikitext, $sectionMatch) !== 1) {
            return [];
        }

        $lines = preg_split('/\R/', $sectionMatch[1]) ?: [];
        $bullets = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || ! str_starts_with($trimmed, '*') || str_starts_with($trimmed, '**')) {
                continue;
            }

            $content = trim(ltrim($trimmed, '*'));

            if ($content !== '') {
                $bullets[] = $content;
            }
        }

        return $bullets;
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

        if (preg_match('/\((\d{1,2}:\d{2})\)\s*$/', $line, $timeMatch) === 1) {
            $time = $timeMatch[1];
            $line = trim(substr($line, 0, -strlen($timeMatch[0])));
        }

        if (preg_match('/\s+(?:defeated|def\.)\s+/i', $line, $verbMatch, PREG_OFFSET_CAPTURE) === 1) {
            return $this->parseDecisiveLine($line, $verbMatch, $cardOrder, $time, $isDark);
        }

        if (preg_match('/\s+ended\s+in\s+/i', $line) === 1) {
            return $this->parseDrawLine($line, $cardOrder, $time, $isDark);
        }

        throw new RuntimeException("Could not parse Nitro match {$cardOrder}.");
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

        if (preg_match('/\s+vs\.?\s+/i', $participantsRaw, $versusMatch, PREG_OFFSET_CAPTURE) !== 1) {
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
            isPpv: false,
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
