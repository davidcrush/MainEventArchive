<?php

namespace App\Services\Streaming;

use App\Data\NetflixCatalogEntry;

class NetflixSavedHtmlParser
{
    /**
     * @return list<NetflixCatalogEntry>
     */
    public function parse(string $html): array
    {
        $html = $this->normalizeHtml($html);
        $entries = [];

        $this->collectEpisodeItems($entries, $html);

        $this->collectFromPattern(
            $entries,
            $html,
            '/aria-label="([^"]+)"[^>]*href="(?:https:\/\/www\.netflix\.com)?\/(?:title|watch)\/(\d+)/i',
            titleIndex: 1,
            idIndex: 2,
        );

        $this->collectFromPattern(
            $entries,
            $html,
            '/href="(?:https:\/\/www\.netflix\.com)?\/(?:title|watch)\/(\d+)[^"]*"[^>]*aria-label="([^"]+)"/i',
            titleIndex: 2,
            idIndex: 1,
        );

        $this->collectFromPattern(
            $entries,
            $html,
            '/aria-label="([^"]+)"[^>]*href="[^"]*[?&]jbv=(\d+)/i',
            titleIndex: 1,
            idIndex: 2,
        );

        $this->collectFromPattern(
            $entries,
            $html,
            '/href="[^"]*[?&]jbv=(\d+)[^"]*"[^>]*aria-label="([^"]+)"/i',
            titleIndex: 2,
            idIndex: 1,
        );

        if (preg_match_all(
            '/href="[^"]*suggestionId=Video(?:%3A|:)(\d+)[^"]*"[^>]*>.*?<p[^>]*>([^<]+)<\/p>/is',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) !== false) {
            foreach ($matches as $match) {
                $this->addEntry($entries, $match[1], $match[2]);
            }
        }

        if ($entries === []) {
            if (preg_match_all(
                '/href="(?:https:\/\/www\.netflix\.com)?\/(?:title|watch)\/(\d+)/i',
                $html,
                $matches,
            ) !== false) {
                foreach ($matches[1] as $titleId) {
                    $this->addEntry($entries, $titleId, "Netflix title {$titleId}");
                }
            }

            if (preg_match_all('/[?&]jbv=(\d+)/i', $html, $matches) !== false) {
                foreach ($matches[1] as $titleId) {
                    $this->addEntry($entries, $titleId, "Netflix title {$titleId}");
                }
            }
        }

        return array_values($entries);
    }

    /**
     * @param  array<string, NetflixCatalogEntry>  $entries
     */
    private function collectEpisodeItems(array &$entries, string $html): void
    {
        if (preg_match_all(
            '/episode-item[^>]*aria-label="([^"]+)"[^>]*>.*?(?:%22video_id%22|"video_id")\s*:\s*(\d+)/is',
            $html,
            $matches,
            PREG_SET_ORDER,
        ) === false) {
            return;
        }

        foreach ($matches as $match) {
            $this->addEntry($entries, $match[2], $match[1], overwrite: true);
        }
    }

    private function normalizeHtml(string $content): string
    {
        if (str_starts_with($content, 'From:') && str_contains($content, 'Content-Transfer-Encoding: quoted-printable')) {
            if (preg_match('/Content-Type: text\/html[\s\S]*?\r?\n\r?\n([\s\S]*?)(?:\r?\n------|$)/i', $content, $match) === 1) {
                $content = $match[1];
            }
        }

        if (str_contains($content, '=3D') || str_contains($content, "=\r\n") || str_contains($content, "=\n")) {
            $decoded = quoted_printable_decode($content);

            if (is_string($decoded) && $decoded !== '') {
                $content = $decoded;
            }
        }

        return html_entity_decode($content, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @param  array<string, NetflixCatalogEntry>  $entries
     */
    private function collectFromPattern(array &$entries, string $html, string $pattern, int $titleIndex, int $idIndex): void
    {
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) === false) {
            return;
        }

        foreach ($matches as $match) {
            $this->addEntry($entries, $match[$idIndex], $match[$titleIndex]);
        }
    }

    /**
     * @param  array<string, NetflixCatalogEntry>  $entries
     */
    private function addEntry(array &$entries, string $titleId, string $title, bool $overwrite = false): void
    {
        $titleId = trim($titleId);
        $title = trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5));

        if ($titleId === '' || ! ctype_digit($titleId)) {
            return;
        }

        if (! $overwrite && isset($entries[$titleId])) {
            return;
        }

        $entries[$titleId] = new NetflixCatalogEntry(
            titleId: $titleId,
            title: $title,
        );
    }
}
