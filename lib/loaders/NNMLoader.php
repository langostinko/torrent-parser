<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class NNMLoader extends AbstractLoader {

    private $result;
    private $link;
    private $data;
    
    function __construct($link, $data){
        $this->result = array();
        $this->link = $link;
        $this->data = $data;
    }

    function processTd($html, &$movie){
        $res = str_get_html($html)->find('a', 0);
        if (!$res)
            return false;

        $movie['link'] = NNMROOT . "/forum/" . $res->href;

        $title = html_entity_decode($res->plaintext, ENT_QUOTES, "UTF-8");
        $movie['description'] = $title;

        $result = array();
        $res1 = preg_match_all('/\[S\d+/isU', $title, $result, PREG_OFFSET_CAPTURE);
        $res2 = preg_match_all('/\[[\d-x ,]+\]/isU', $title, $result, PREG_OFFSET_CAPTURE);
        if ($res1 || $res2) {//that's a series
            $this->logger->info("skip series: " . $title);
            return false;
        }

        extractTranslate($title, $movie);
        $pos = strpos($title, '[');
        if ($pos !== FALSE)
            $title = trim(substr($title, 0, $pos));
        $pos = strrpos($title, '/') + 1;
        if ($pos > 4)
            $title = trim(substr($title, $pos));
        extractString($title, $movie);

        if (trySkip($movie))
            return false;

        return true;
    }

    function processTr($html){
        $result = array();
        preg_match_all("/<td.*td>/sU", $html, $result);
        $curTr = $result[0];
        if (count($result[0]) != 8)
            return;

        $movie = array();
            
        $timeTd = str_get_html($curTr[7]);
        $movie['added_tracker'] = $timeTd->find('u',0)->plaintext;
        if ( (time() - $movie['added_tracker']) / 3600 / 24 > ADDLINKSPASTDAYS)
            return false;

        $sizeTd = str_get_html($curTr[3]);
        $movie['size'] = (float)$sizeTd->find('u',0)->plaintext;
        $movie['size'] /= 1024*1024;

        $seedTd = str_get_html($curTr[4]);
        $leechTd = str_get_html($curTr[5]);
        $movie['seed'] = (int)$seedTd->plaintext;
        $movie['leech'] = (int)$leechTd->plaintext;

        $res = $this->processTd($curTr[1], $movie);
            
        if (!$res)
            return false;

        $this->result[] = $movie;
        return true;
    }
    
    function callback($response, $info) {
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $this->logger->warning($msg);
            return;
        }
        $this->logger->info($msg);

        $response = iconv("windows-1251", "UTF-8", $response);

        $result = array();
        preg_match_all("/<tr class=\"prow.*tr>/sU", $response, $result);
        foreach ($result[0] as $tr) {
            $this->processTr($tr);
        }

		$this->logger->info(count($this->result) . " new links found");
    }

    function load() {
        $this->result = array();

        \RollingCurl::$rc->post($this->link, $this->data, null, null, array("callback"=>array($this, "callback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>