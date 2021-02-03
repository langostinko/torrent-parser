<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../simple_html_dom.php');

class GooglePlayLoader extends AbstractLoader {

    private $link;
    
    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }
    
    function getGooglePlayCallback($response, $info) {
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
        foreach ($html->find("div[class=ImZGtf mpg5gc]") as $row) {
            $movie = array();
            $div = $row->find("div[class=b8cIId ReQCgd Q9MA7b]", 0);
            $a = $div->find("a", 0);
            $div_title = $div->find("div", 0);
            $movie['link'] = "https://play.google.com" . $a->href;
            $movie['title'] = $movie['title_approx'] = $div_title->title;
            $movie['size'] = (float)(str_replace(',', '.',$row->find("span[class=VfPpfd ZdBevf i5DZme]",0)->plaintext));
            $movie["description"] = json_encode(array(
                "title" => $movie["title"],
                "options" => array("rent_sd" => $movie['size']),
                ));
            $movie['quality'] = "WEB";
            $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
            $movie['type'] = 1;
            $movie['seed'] = $movie['leech'] = 0;
            $this->result[] = $movie;
        }

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();
        
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getGooglePlayCallback")) );
    }

}
?>
