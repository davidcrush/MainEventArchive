<?php

namespace App\Services\Fandom;

use App\Services\Wikipedia\Concerns\BuildsMatchParticipants;

/**
 * Extracts venue/city from a Fandom "{{Infobox Wrestling episode}}" block, e.g.:
 *
 *   {{Infobox Wrestling episode
 *   | date = [[January 1]], [[1996]]
 *   | venue = [[The Omni]]
 *   | city = [[Atlanta, Georgia]]
 *   }}
 *
 * @phpstan-type ParsedNitroInfobox array{venue: ?string, city: ?string}
 */
class FandomNitroInfoboxParser
{
    use BuildsMatchParticipants;

    /**
     * @return ParsedNitroInfobox
     */
    public function parse(string $wikitext): array
    {
        return [
            'venue' => $this->field($wikitext, 'venue'),
            'city' => $this->field($wikitext, 'city'),
        ];
    }

    private function field(string $wikitext, string $key): ?string
    {
        if (preg_match('/\n\s*\|\s*'.preg_quote($key, '/').'\s*=\s*([^\n|}]+)/i', $wikitext, $match) !== 1) {
            return null;
        }

        $value = $this->stripWikiMarkup($match[1]);

        return $value !== '' ? $value : null;
    }
}
