<?php

namespace App\Services\Streaming;

use InvalidArgumentException;

class NetflixUrlParser
{
    /**
     * @return array{external_id: string, url: string}
     */
    public function parse(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidArgumentException('Netflix URL or title ID is required.');
        }

        if (preg_match('/(?:netflix\.com\/(?:watch|title)\/|\/(?:watch|title)\/)(\d+)/i', $input, $matches) === 1) {
            $titleId = $matches[1];

            return $this->buildReference($titleId);
        }

        if (preg_match('/^\d{5,}$/', $input) === 1) {
            return $this->buildReference($input);
        }

        throw new InvalidArgumentException('Could not parse Netflix title ID from input.');
    }

    /**
     * @return array{external_id: string, url: string}
     */
    private function buildReference(string $titleId): array
    {
        $template = config('streaming.netflix.watch_url_template', 'https://www.netflix.com/watch/%s');

        return [
            'external_id' => $titleId,
            'url' => sprintf($template, $titleId),
        ];
    }
}
