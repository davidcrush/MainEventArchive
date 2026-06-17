<?php

namespace App\Services\Wikipedia\Concerns;

/**
 * Format-agnostic helpers shared by results parsers (Wikipedia, Fandom, etc.):
 * splitting sides into participants, normalizing wiki markup, detecting the
 * defending champion, and applying spoiler-safe side ordering.
 */
trait BuildsMatchParticipants
{
    /**
     * Determine which original side holds the defending champion, based on the
     * "(c)" marker. The winner is parsed as side 1 and the losers as side 2+,
     * so the champion marker maps to side 1 (winner) or the lowest loser side
     * (2) when present.
     */
    protected function detectChampionSide(string $winnerRaw, string $loserAndFinishRaw): ?int
    {
        if (stripos($winnerRaw, '(c)') !== false) {
            return 1;
        }

        if (stripos($loserAndFinishRaw, '(c)') !== false) {
            return 2;
        }

        return null;
    }

    /**
     * Reorder match sides so the stored order does not leak the winner when
     * spoilers are off. Championship matches list the champion first; other
     * matches use a deterministic, winner-independent shuffle. The returned
     * winnerSide is remapped to the side's new position.
     *
     * @param  list<array{name: string, side: int, sort_order: int}>  $participants
     * @return array{participants: list<array{name: string, side: int, sort_order: int}>, winnerSide: int}
     */
    protected function applySpoilerSafeSideOrder(
        array $participants,
        int $cardOrder,
        ?string $titleName,
        ?int $championSide,
        int $winnerSide = 1,
    ): array {
        $sides = [];

        foreach ($participants as $participant) {
            $sides[$participant['side']][] = $participant;
        }

        $sideKeys = array_keys($sides);
        sort($sideKeys);

        if (count($sideKeys) < 2) {
            return ['participants' => $participants, 'winnerSide' => $winnerSide];
        }

        if ($titleName !== null && $championSide !== null && in_array($championSide, $sideKeys, true)) {
            $orderedSides = array_merge(
                [$championSide],
                array_values(array_filter($sideKeys, static fn (int $key): bool => $key !== $championSide)),
            );
        } else {
            $orderedSides = $this->deterministicSideOrder($sides, $sideKeys, $cardOrder);
        }

        $reordered = [];
        $newWinnerSide = $winnerSide;

        foreach ($orderedSides as $newIndex => $oldSide) {
            $newSide = $newIndex + 1;

            if ($oldSide === $winnerSide) {
                $newWinnerSide = $newSide;
            }

            foreach ($sides[$oldSide] as $participant) {
                $participant['side'] = $newSide;
                $reordered[] = $participant;
            }
        }

        return ['participants' => $reordered, 'winnerSide' => $newWinnerSide];
    }

    /**
     * Produce a stable, winner-independent ordering of side keys. The order is
     * seeded by card position and the full (sorted) participant roster so it is
     * identical across page loads and re-imports but reveals nothing about who
     * won.
     *
     * @param  array<int, list<array{name: string, side: int, sort_order: int}>>  $sides
     * @param  list<int>  $sideKeys
     * @return list<int>
     */
    protected function deterministicSideOrder(array $sides, array $sideKeys, int $cardOrder): array
    {
        $allNames = [];

        foreach ($sides as $members) {
            foreach ($members as $participant) {
                $allNames[] = $participant['name'];
            }
        }

        sort($allNames, SORT_STRING);
        $seed = $cardOrder.'|'.implode('|', $allNames);

        $sideHashes = [];

        foreach ($sideKeys as $sideKey) {
            $names = array_map(static fn (array $participant): string => $participant['name'], $sides[$sideKey]);
            sort($names, SORT_STRING);
            $sideHashes[$sideKey] = md5($seed.'#'.implode('&', $names));
        }

        $ordered = $sideKeys;
        usort($ordered, static fn (int $a, int $b): int => strcmp($sideHashes[$a], $sideHashes[$b]));

        return $ordered;
    }

    /**
     * @return list<array{name: string, side: int, sort_order: int}>
     */
    protected function parseTeamsFromSide(string $raw, int $startingSide, bool $combineSegments): array
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
    protected function extractTeamSegments(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $segments = [];
        $current = '';
        $parenDepth = 0;
        $wikiLinkDepth = 0;
        $length = strlen($text);

        for ($index = 0; $index < $length; $index++) {
            if ($wikiLinkDepth === 0 && substr($text, $index, 2) === '[[') {
                $wikiLinkDepth++;
                $current .= '[[';
                $index++;

                continue;
            }

            if ($wikiLinkDepth > 0 && substr($text, $index, 2) === ']]') {
                $wikiLinkDepth--;
                $current .= ']]';
                $index++;

                continue;
            }

            $character = $text[$index];

            if ($wikiLinkDepth === 0) {
                if ($character === '(') {
                    $parenDepth++;
                } elseif ($character === ')') {
                    $parenDepth--;
                }
            }

            if ($parenDepth === 0 && $wikiLinkDepth === 0) {
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

    protected function formatTeamSegment(string $segment): string
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
    protected function extractMemberNames(string $clause): array
    {
        preg_match_all('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', $clause, $linkMatches);

        if ($linkMatches[1] !== []) {
            return array_values(array_unique(array_map('trim', $linkMatches[1])));
        }

        return [];
    }

    protected function stripWikiMarkup(string $value): string
    {
        $value = $this->removeReferenceTags($value);
        $value = preg_replace('/\{\{[^}]+\}\}/', '', $value) ?? $value;
        $value = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', '$1', $value) ?? $value;
        $value = preg_replace("/'''+/", '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    protected function removeReferenceTags(string $value): string
    {
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*\/>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*?<\/ref>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*/is', '', $value) ?? $value;

        return trim($value);
    }

    protected function removeManagerClauses(string $text): string
    {
        $text = preg_replace(
            '/\(with (?:\[\[[^\]]+\]\](?:\s+and\s+\[\[[^\]]+\]\])*)\)/i',
            '',
            $text,
        ) ?? $text;

        $text = preg_replace('/\(with [^)]+\)/i', '', $text) ?? $text;

        return preg_replace('/\(w\/[^)]*\)/i', '', $text) ?? $text;
    }

    protected function isTriangleMatch(string $stipulation): bool
    {
        return str_contains(strtolower($this->stripWikiMarkup($stipulation)), 'triangle');
    }

    protected function segmentDelimiterLength(string $text, int $index): ?int
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

    protected function resolveMatchType(string $stipulation, string $finish): string
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

    protected function parseDuration(string $time): ?int
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
