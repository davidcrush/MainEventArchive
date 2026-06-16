<?php

namespace App\Exceptions;

use RuntimeException;

class WikipediaMatchCountMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $declaredCount,
        public readonly int $parsedCount,
    ) {
        parent::__construct(self::formatMessage($declaredCount, $parsedCount));
    }

    public static function formatMessage(int $declaredCount, int $parsedCount): string
    {
        return "Wikipedia lists {$declaredCount} matches but only {$parsedCount} were parsed successfully.";
    }
}
