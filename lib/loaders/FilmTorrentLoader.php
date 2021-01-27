<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class FilmTorrentLoader extends AbstractLoader {

    private $link;
    
    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }
    
    function getFilmTorrentMovieCallback($response, $info) {
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

        foreach ($html->find("table[class=res85gtj] tr") as $row) {
            $movie = array();

            $title = $row->find("td", 1);
            if (!$title) {
                continue;
            }
            $title = html_entity_decode($row->find("td", 1)->find("div b", 0)->plaintext, ENT_QUOTES, "UTF-8");
            extractString($title, $movie);
            extractTranslate($title, $movie);

            $size = html_entity_decode($row->find("td", 2)->plaintext);
            $movie['size'] = (float)$size;
            if (strpos($size, 'G'))
                $movie['size'] *= 1024;

        	$movie['seed'] = (int)$row->find("td", 3)->plaintext;
        	$movie['leech'] = (int)$row->find("td", 4)->plaintext;
            $movie['link'] = $info['url'] . "?it=" . $movie['size'] . $movie['quality'];
            $movie['description'] = $title;
            if (!trySkip($movie))
                $this->result[] = $movie;
        }
    }

    function getFilmTorrentCallback($response, $info) {
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

        foreach ($html->find("div[class=post-title] a") as $row) {
            \RollingCurl::$rc->get($row->href, null, null, array("callback"=>array($this, "getFilmTorrentMovieCallback")) );
        }
    }

    function load() {
        $this->result = array();
        
        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getFilmTorrentCallback")) );
    }

}
?>