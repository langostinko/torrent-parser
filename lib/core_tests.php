<?php
require_once "core.php";

use PHPUnit\Framework\TestCase;

final class CoreTests extends TestCase
{
    public function testExtractTranslate(): void
    {
        {
            $movie = array();
            extractTranslate("Мэнди / Mandy (2018) WEB-DLRip-AVC от OlLanDGroup | BadBajo", $movie);
            $this->assertEquals("BADBAJO", $movie['translateQuality']);
        }
        {
            $movie = array();
            extractTranslate("Мэнди / Mandy (2018) WEB-DLRip от ExKinoRay l BadBajo", $movie);
            $this->assertEquals("BADBAJO", $movie['translateQuality']);
        }
        {
            $movie = array();
            extractTranslate("Мэнди / Mandy (2018) WEB-DL [H.264/1080p-LQ] [AVO]", $movie);
            $this->assertEquals("AVO", $movie['translateQuality']);
        }
        {
            $movie = array();
            extractTranslate("Небоскрёб / Skyscraper (2018) WEB-DL 1080p | iTunes", $movie);
            $this->assertEquals("ITUNES", $movie['translateQuality']);
        }
    }
}
?>
