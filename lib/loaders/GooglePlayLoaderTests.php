<?php
require_once "GooglePlayLoader.php";

use PHPUnit\Framework\TestCase;

final class GooglePlayLoaderTests extends TestCase
{
    public function testHttpCode500(): void
    {
        global $logger;
        $loader = new GooglePlayLoader("");
        $loader->setLogger($logger);
        $loader->getGooglePlayCallback("", array("http_code" => 500, "url" => "", "total_time" => 0));
        $result = $loader->getResult();
        $this->assertEquals(count($result), 0);
    }
    
    public function testParseMovie(): void
    {
        global $logger;
        $loader = new GooglePlayLoader("");
        $html = '<div class="ImZGtf mpg5gc"><c-wiz jsrenderer="PAQZbb" jsshadow="" jsdata="deferred-i11" data-p="%.@.true]" jscontroller="JpEzfb" jsaction="aM6rcc:MRWqkd" data-node-index="1;0" autoupdate="" jsmodel="hc6Ubd"><div class="uMConb  V2Vq5e POHYmb-T8c9cb YEDFMc-T8c9cb y1APZe-T8c9cb q9QOMe" jslog="38003; 1:500|CAIaLgoVEhMKDU0wZnFVUkJNc2JzLlAQBhgEEAAyEwjY+cnsx8nuAhVEtKQKHaRtDieqAjAaLggAEhUKEwoNTTBmcVVSQk1zYnMuUBAGGARKEwjY+cnsx8nuAhVEtKQKHaRtDic=; track:click,impression"><div class="Vpfmgd"><div class="uzcko"><div class="N9c7d eJxoSc"><span class="yNWQ8e K3IMke " style="height: 230px;width: 160px"><img data-src="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w160-h230" data-ils="3" jsaction="rcuQ6b:trigger.M8vzZb;" data-srcset="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w320-h460 2x" class="T75of QNCnCf" aria-hidden="true" style="max-height: 230px;max-width: 160px;height: 230px"></span><span class="ZYyTud K3IMke " style="height: 230px;width: 160px"><img data-src="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w160-h230" data-ils="3" jsaction="rcuQ6b:trigger.M8vzZb;" data-srcset="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w320-h460 2x" class="T75of QNCnCf" aria-hidden="true" style="max-height: 230px;max-width: 160px;height: 230px"></span><span class="kJ9uy K3IMke " style="height: 230px;width: 160px"><img data-ils="3" jsaction="rcuQ6b:trigger.M8vzZb;" class="T75of QNCnCf" aria-hidden="true" style="max-height: 230px;max-width: 160px;height: 230px" srcset="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w320-h460 2x" src="https://play-lh.googleusercontent.com/0GFnkqcLx67EOD9t93truV0RtHcUi3QSj8WJfRDVEs7h-UaRU9PzgPPfaFOqvX4FCJfEFnV1DfGKG253ewA=w160-h230"></span><div class="wXUyZd"><button aria-label="Смотреть &quot;ДОВОД&quot;" class="MMZjL sgOwM  " jscontroller="HnDLGf" jsaction="click:axChxd" jsname="pWHZ7d" data-should-show-kav="true" data-trailer-url="https://play.google.com/video/lava/web/player/yt:movie:28IhiOS3hec.P?autoplay=1&amp;authuser=0&amp;embed=play" data-web-presentation="1" data-item-id="M0fqURBMsbs.P" data-item-type="1"></button></div></div></div><div class="RZEgze"><div class="vU6FJ p63iDd"><a href="/store/movies/details/%D0%94%D0%9E%D0%92%D0%9E%D0%94?id=M0fqURBMsbs.P" aria-hidden="true" tabindex="-1" class="JC71ub"></a><div class="k6AFYd"><div class="bQVA0c"><div class="PODJt"><div class="kCSSQe"><div class="b8cIId ReQCgd Q9MA7b"><a href="/store/movies/details/%D0%94%D0%9E%D0%92%D0%9E%D0%94?id=M0fqURBMsbs.P"><div class="WsMG1c nnK0zc" title="ДОВОД">ДОВОД</div></a><div class="cqtbn"></div></div><div>Звуковые дорожки на нескольких языках</div><div class="b8cIId f5NCO"><a href="/store/movies/details/%D0%94%D0%9E%D0%92%D0%9E%D0%94?id=M0fqURBMsbs.P">Вооружившись лишь одним словом&nbsp;— «Довод»&nbsp;— и сражаясь за выживание всего мира, протагонист погружается в дебри международного шпионажа с заданием, что выведет его за пределы реального времени.</a><div class="cqtbn xKFUib"></div></div></div></div></div><div class="Z2nl8b"><div class="PODJt"><div class="kCSSQe"><div class="pf5lIe"><div aria-label="Средняя оценка: 3,7 из 5" role="img"><div class="vQHuPe bUWb7c"></div><div class="vQHuPe bUWb7c"></div><div class="vQHuPe bUWb7c"></div><div class="L0jl5e bUWb7c cm4lTe"><div class="vQHuPe bUWb7c D3FNOd" style="width: 66.26505851745605%"></div></div><div class="L0jl5e bUWb7c"></div></div></div></div><div class="zYPPle"><div jsname="zVnJac"><button class="svCDYe aYzfud YpSFl  " jscontroller="chfSwc" jsaction="MH7vAb" jsmodel="UfnShf" data-item-id="%.@.&quot;M0fqURBMsbs.P&quot;,1]" data-require-confirmation-if-single-offer="true" jslog="36906; 1:200|CAIaLgoVEhMKDU0wZnFVUkJNc2JzLlAQBhgEEAAyEwjY+cnsx8nuAhVEtKQKHaRtDieqAjAaLggAEhUKEwoNTTBmcVVSQk1zYnMuUBAGGARKEwjY+cnsx8nuAhVEtKQKHaRtDic=; track:click,impression"><div class="LCATme"><span class="VfPpfd ZdBevf i5DZme"><span>5,49&nbsp;£</span></span></div></button></div></div></div></div></div></div></div></div></div><c-data id="i11" jsdata=" OhlBSe;M0fqURBMsbs.P,1;9 UbHxed;_;5 QwEV2c;M0fqURBMsbs.P,1;10"></c-data></c-wiz></div>';
        $loader->setLogger($logger);
        $loader->getGooglePlayCallback($html, array("http_code" => 200, "url" => "", "total_time" => 0));
        $result = $loader->getResult();
        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]['link'], 'https://play.google.com/store/movies/details/%D0%94%D0%9E%D0%92%D0%9E%D0%94?id=M0fqURBMsbs.P');
        $this->assertEquals($result[0]['size'], 5.49);
        $this->assertEquals($result[0]['title'], "ДОВОД");
    }

}
?>