<?php

namespace App\Exceptions;

use App\Models\Show;
use RuntimeException;

class WikipediaImportResolutionException extends RuntimeException
{
    /**
     * @param  list<array{title: string, reason: string}>  $attempts
     */
    public function __construct(
        public readonly Show $show,
        public readonly array $attempts,
    ) {
        parent::__construct(self::formatMessage($show, $attempts));
    }

    /**
     * @param  list<array{title: string, reason: string}>  $attempts
     */
    public static function formatMessage(Show $show, array $attempts): string
    {
        $lines = [
            "Could not import Wikipedia card for [{$show->title}] (slug: {$show->slug}, date: {$show->date->toDateString()}).",
        ];

        if ($attempts === []) {
            $lines[] = 'No Wikipedia page candidates were tried.';

            return implode("\n", $lines);
        }

        $lines[] = 'Attempts:';

        foreach ($attempts as $attempt) {
            $lines[] = "  - {$attempt['title']}: {$attempt['reason']}";
        }

        return implode("\n", $lines);
    }
}
