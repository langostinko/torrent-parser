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
            $movie['seed'] = $movie['leech'] = 0;
            $movie['type'] = 1;
            $movies[$megogoId] = $movie;
            $priceReq .= $megogoId . "n";
		}
		
		$prices = file_get_contents_curl($priceReq);
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
                $movie["size"] = 1<<10;
                $priceTypes = array(
                    "svod"=>"sub",
                    "tvod"=>"rent_sd",
                    "dtr"=>"rent_sd",
                    "dto"=>"buy_sd",
                    );
                $movie['description'] = array(
                    "title" => $movie["title"] . " (" . $movie["year"] . ")",
                    "options" => array()
                );
		        foreach($priceTypes as $priceKey=>$priceVal)
    		        if (array_key_exists("price", $price[$priceKey])) {
    		            $movie["size"] = min($movie["size"], (int)$price[$priceKey]["price"]);
                        $movie['description']['options'][$priceVal] = (int)$price[$priceKey]["price"];
    		        }
                $movie['description'] = json_encode($movie['description']);
                if (!trySkip($movie))
                    $this->result[] = $movie;
    		}

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();
        
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getMegogoCallback")) );
    }

}
?>