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
        $movie = array();
        $movie["link"] = "https://itunes.apple.com/ru/movie/id" . $costRes['trackId'];
        $movie["title_approx"] = $costRes["trackName"];
        $movie["title"] = $costRes["trackName"];
        $movie["year"] = substr($costRes["releaseDate"], 0, 4);
        $movie["size"] = 0;
        $movie['quality'] = "WEB";
        $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
        $movie['type'] = 1;
        $movie['seed'] = $movie['leech'] = 0;
        $priceTypes = array(
            "trackPrice"=>"",
            "trackRentalPrice"=>" (аренда)",
            "trackHdPrice"=>" HD",
            "trackHdRentalPrice"=>" HD (аренда)"
            );
        foreach($priceTypes as $pKey=>$pVal) {
            if (array_key_exists($pKey, $costRes)) {
                $added = &$this->result[];
                $added = $movie;
                $added["description"] = "iTunes" . $pVal . " : " . $movie["title"] . " (" . $movie["year"] . ")";
                $added["size"] = (int)$costRes[$pKey];
                $added["link"].="?type=$pKey";
                if (trySkip($added))
                    array_pop($this->result);
            }
        }
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

        $act = $html->find('section[class=section movies chart-grid]',0);
        if ($act)
            foreach ($act->find("li") as $row) {
                $link = $row->find('a', 0)->href;
                preg_match("/\/id(\d+)/", $link, $matches);
                $id = $matches[1];
                $costLink = "https://itunes.apple.com/lookup?country=RU&id=$id";
                \RollingCurl::$rc->get($costLink, null, null, array("callback"=>array($this, "getITunesCostCallback")) );
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