<?php

namespace App\Services\Cagematch;

use RuntimeException;

class CagematchSavedPageLoader
{
    public function load(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read Cagematch save at {$path}");
        }

        if (! str_contains($contents, 'MultipartBoundary')) {
            return $contents;
        }

        if (preg_match('/Content-Type: text\/html[\s\S]*?\r?\n\r?\n([\s\S]*?)\r?\n------MultipartBoundary/s', $contents, $matches) !== 1) {
            throw new RuntimeException('Unable to extract HTML from MHTML save.');
        }

        return quoted_printable_decode($matches[1]);
    }
}
