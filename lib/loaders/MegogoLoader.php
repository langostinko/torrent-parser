<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class MegogoLoader extends AbstractLoader {

    private $result;
    private $link;
    
    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }
    
    function getMegogoCallback($response, $info) {
        //some code
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

        $movies = array();
        $priceReq = "http://megogo.net/ru/view/gpi?sd=false&ids=";
        foreach($html->find('li[class=voi]') as $li) {
            $movie = array();
            $megogoId = $li->getAttribute("data-video-id");
            $a = $li->find('a[class=voi__title-link]', 0);
            $movie['link'] = $a->href;
            $movie['year'] = (int)trim($li->find('p[class=voi__info]', 0)->plaintext);
            $movie['title'] = $movie['title_approx'] = trim($a->plaintext);

            $movie['quality'] = "WEB";
            $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
            $movie['type'] = 1;
            $movies[$megogoId] = $movie;
            $priceReq .= $megogoId . "n";
		}
		
		$prices = file_get_contents($priceReq);
		if (!$prices) {
		    $this->logger->warning("failed to get prices");
		    return;
		}
		$prices = json_decode($prices, true);
		if (!$prices || !array_key_exists("data", $prices)) {
		    $this->logger->warning("failed to json decode prices");
		    return;
		}
		
		foreach($prices["data"] as $key=>$price) 
		    if (array_key_exists($key, $movies)) {
		        $movie = $movies[$key];
		        if (array_key_exists("price", $price['svod'])) {
		            $added = &$this->result[];
		            $added = $movie;
                    $added["description"] = "MEGOGO (подписка) : " . $movie["title"] . " (" . $movie["year"] . ")";
		            $added["size"] = $price['svod']["price"];
		            $added["link"].="?type=svod";
		        }
		        if (array_key_exists("price", $price['tvod'])) {
		            $added = &$this->result[];
		            $added = $movie;
                    $added["description"] = "MEGOGO (аренда) : " . $movie["title"] . " (" . $movie["year"] . ")";
		            $added["size"] = $price['tvod']["price"];
		            $added["link"].="?type=tvod";
		        }
		        if (array_key_exists("price", $price['dtr'])) {
		            $added = &$this->result[];
		            $added = $movie;
                    $added["description"] = "MEGOGO (аренда) : " . $movie["title"] . " (" . $movie["year"] . ")";
		            $added["size"] = $price['dtr']["price"];
		            $added["link"].="?type=dtr";
		        }
		        if (array_key_exists("price", $price['dto'])) {
		            $added = &$this->result[];
		            $added = $movie;
                    $added["description"] = "MEGOGO : " . $movie["title"] . " (" . $movie["year"] . ")";
		            $added["size"] = $price['dto']["price"];
		            $added["link"].="?type=dto";
		        }
    		}

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();
        
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getMegogoCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>