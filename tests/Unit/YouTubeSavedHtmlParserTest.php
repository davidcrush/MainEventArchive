<?php

namespace Tests\Unit;

use App\Services\YouTube\YouTubeSavedHtmlParser;
use App\Services\YouTube\YouTubeTitleParser;
use PHPUnit\Framework\TestCase;

class YouTubeSavedHtmlParserTest extends TestCase
{
    public function test_parses_full_event_entries_and_skips_episodes(): void
    {
        $html = <<<'HTML'
        <a id="video-title" title="FULL EVENT: WCW Halloween Havoc 1993 | Cactus Jack and Vader Spin the Wheel, Make the Deal"
           href="https://www.youtube.com/watch?v=ftPK-rYz7Vc">Halloween Havoc 1993</a>
        <a id="video-title" title="FULL EPISODE: Hogan vs. Savage; Goldberg vs. Regal: WCW Monday Nitro, Feb. 9, 1998"
           href="https://www.youtube.com/watch?v=SOtA-nogmtc">Nitro</a>
        <a id="video-title" title="FULL EVENT: WCW SuperBrawl V | Hulk Hogan vs. Vader"
           href="https://www.youtube.com/watch?v=NQrv6j1kaTQ">SuperBrawl V</a>
        HTML;

        $parser = new YouTubeSavedHtmlParser(new YouTubeTitleParser);
        $entries = $parser->parse($html);

        $this->assertCount(2, $entries);
        $this->assertSame('ftPK-rYz7Vc', $entries[0]->videoId);
        $this->assertSame('NQrv6j1kaTQ', $entries[1]->videoId);
    }
}
