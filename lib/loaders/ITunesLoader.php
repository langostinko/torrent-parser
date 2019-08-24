<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class ITunesLoader extends AbstractLoader {

    private $result;
    private $link;

    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }
    
    function getITunesCostCallback($response, $info, $request) {
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $this->logger->warning($msg);
            return;
        }
        $res = json_decode($response, true);
        $costRes = $res['results'][0];
        if (!$costRes) {
            // not available at RU
            return;
        }
        $movie = array();
        $movie["link"] = "https://itunes.apple.com/ru/movie/id" . $costRes['trackId'];
        $movie["title_approx"] = $costRes["trackName"];
        $movie["title"] = $costRes["trackName"];
        $movie["year"] = substr($costRes["releaseDate"], 0, 4);
        $movie["size"] = 1<<20;
        $movie['quality'] = "WEB";
        $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
        $movie['type'] = 1;
        $movie['seed'] = $movie['leech'] = 0;
        $priceTypes = array(
            "trackPrice"=>"buy_sd",
            "trackRentalPrice"=>"rent_sd",
            "trackHdPrice"=>"buy_hd",
            "trackHdRentalPrice"=>"rent_hd"
            );
        $movie['description'] = array(
            "title" => $movie["title"] . " (" . $movie["year"] . ")",
            "options" => array()
        );
        foreach($priceTypes as $pKey=>$pVal)
            if (array_key_exists($pKey, $costRes)) {
                $movie["size"] = min($movie["size"], (int)$costRes[$pKey]);
                $movie['description']['options'][$priceTypes[$pKey]] = (int)$costRes[$pKey];
            }
        $movie['description'] = json_encode($movie['description']);
        if ($movie["size"] != 1<<20 && !trySkip($movie))
            $this->result[] = $movie;
    }
    
    function getITunesCallback($response, $info) {
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $this->logger->warning($msg);
            return;
        }
        $this->logger->info($msg);

		$html = str_get_html($response);
		if (!$html) {
		    $this->logger->warning("failed to convert DOM");
		    return;
		}

        $section = $html->find('section[class=section movies chart-grid]',0);
        if ($section) {
            foreach ($section->find("li") as $row) {
                $link = $row->find('a', 0)->href;
                preg_match("/\/id(\d+)/", $link, $matches);
                $id = $matches[1];
                $costLink = "https://itunes.apple.com/lookup?country=RU&id=$id";
                \RollingCurl::$rc->get($costLink, null, null, array("callback"=>array($this, "getITunesCostCallback")) );
            }
        }
    }

    function load() {
        $this->result = array();
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getITunesCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>