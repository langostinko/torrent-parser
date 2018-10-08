<?php
require_once "core.php";

use PHPUnit\Framework\TestCase;

final class CoreTests extends TestCase
{
    public function testExtractTranslate(): void
    {
        $movie = array();

        extractTranslate("Мэнди / Mandy (2018) WEB-DLRip-AVC от OlLanDGroup | BadBajo", $movie);
        $this->assertEquals("BADBAJO", $movie['translateQuality']);

        extractTranslate("Не оставляй следов / Leave No Trace (2018) WEB-DLRip | LakeFilms", $movie);
        $this->assertEquals("LAKEFILMS", $movie['translateQuality']);

        extractTranslate("Апгрейд / Upgrade (2018) BDRip 1080p от qqss44 & MegaPeer | Jaskier", $movie);
        $this->assertEquals("JASKIER", $movie['translateQuality']);

        extractTranslate("Мэнди / Mandy (2018) WEB-DLRip от ExKinoRay l BadBajo", $movie);
        $this->assertEquals("BADBAJO", $movie['translateQuality']);

        extractTranslate("Мэнди / Mandy (2018) WEB-DL [H.264/1080p-LQ] [AVO]", $movie);
        $this->assertEquals("AVO", $movie['translateQuality']);

        extractTranslate("Небоскрёб / Skyscraper (2018) WEB-DL 1080p | iTunes", $movie);
        $this->assertEquals("ITUNES", $movie['translateQuality']);
    }
}
?>
