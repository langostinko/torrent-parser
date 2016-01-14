<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class GooglePlayLoader extends AbstractLoader {

    private $result;
    private $link;
    
    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }
    
    function getGooglePlayCallback($response, $info) {
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

        $currentYear = date("Y");
        foreach ($html->find("div[class=card no-rationale tall-cover movies small]") as $row) {
            $movie = array();
            $a = $row->find('a[class=title]', 0);
            $movie['link'] = "https://play.google.com" . $a->href;
            $movie['title'] = $movie['title_approx'] = $a->title;
            $movie['year'] = $currentYear;
            $movie['size'] = (int)($row->find('span[class=display-price]',0)->plaintext);
            $movie["description"] = "Google Play : " . $movie["title"];
            $movie['quality'] = "WEB";
            $movie['translateQuality'] = "ЛИЦЕНЗИЯ";
            $movie['type'] = 1;
            $this->result[] = $movie;
        }

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();
        
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getGooglePlayCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>