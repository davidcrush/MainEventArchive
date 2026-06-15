<?php

namespace App\Services\Cagematch;

use App\Data\CagematchEvent;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;

class CagematchListingParser
{
    /**
     * @return list<CagematchEvent>
     */
    public function parse(string $html): array
    {
        $document = $this->loadHtml($html);
        $xpath = new DOMXPath($document);
        $events = [];
        $seenIds = [];

        /** @var \DOMNodeList<int, DOMElement> $links */
        $links = $xpath->query('//a[@href]');

        if ($links === false) {
            return [];
        }

        foreach ($links as $link) {
            $eventId = $this->extractEventId($link->getAttribute('href'));

            if ($eventId === null || isset($seenIds[$eventId])) {
                continue;
            }

            $row = $this->findTableRow($link);

            if ($row === null) {
                continue;
            }

            $date = $this->extractDateFromRow($row);

            if ($date === null) {
                continue;
            }

            $title = trim($link->textContent);

            if ($title === '') {
                continue;
            }

            $seenIds[$eventId] = true;
            $events[] = new CagematchEvent($eventId, $title, $date);
        }

        return $events;
    }

    private function loadHtml(string $html): DOMDocument
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        return $document;
    }

    private function extractEventId(string $href): ?int
    {
        $decoded = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);

        if (preg_match('/(?:[?&;]|&amp;)id=1(?:&|&amp;)nr=(\d+)/i', $decoded, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function findTableRow(DOMElement $node): ?DOMElement
    {
        $current = $node->parentNode;

        while ($current instanceof DOMElement) {
            if (strtolower($current->tagName) === 'tr') {
                return $current;
            }

            $current = $current->parentNode;
        }

        return null;
    }

    private function extractDateFromRow(DOMElement $row): ?Carbon
    {
        foreach ($row->getElementsByTagName('td') as $cell) {
            $text = trim(preg_replace('/\s+/', ' ', $cell->textContent) ?? '');

            if ($text === '') {
                continue;
            }

            if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $text, $matches) === 1) {
                return Carbon::createFromFormat('Y-m-d', "{$matches[3]}-{$matches[2]}-{$matches[1]}")->startOfDay();
            }
        }

        return null;
    }
}
