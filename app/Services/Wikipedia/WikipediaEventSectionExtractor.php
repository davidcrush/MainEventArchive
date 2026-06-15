<?php

namespace App\Services\Wikipedia;

class WikipediaEventSectionExtractor
{
    public function extract(string $wikitext, string ...$headings): ?string
    {
        $headings = array_values(array_unique(array_filter(
            $headings,
            static fn (string $heading): bool => trim($heading) !== '',
        )));

        foreach ($headings as $heading) {
            $heading = trim($heading);
            $escapedHeading = preg_quote($heading, '/');

            foreach ([4, 3, 5] as $headingLevel) {
                $equals = str_repeat('=', $headingLevel);
                $pattern = '/\n'.$equals.'\s*'.$escapedHeading.'\s*'.$equals.'(.+?)(?=\n'.$equals.'|\n={1,'.($headingLevel - 1).'}[^=]|$)/is';

                if (preg_match($pattern, "\n".$wikitext, $matches) === 1) {
                    return $matches[1];
                }
            }
        }

        return null;
    }
}
