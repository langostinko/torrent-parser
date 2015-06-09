<?php
include_once(__DIR__."/AbstractLoader.php");
include_once(__DIR__.'/../defines.php');
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class RutorLoader extends AbstractLoader {

    private $result;
    private $link;
    
    function __construct($link) {
        $this->result = array();
        $this->link = $link;
    }

    function processTd($html, &$movie){
        $res = $html->find('a',1);
        if (!$res)
            return false;
        $movie['link'] = RUTORROOT.$res->href;
        if (trySkip($movie))
            return false;
        
        $title = html_entity_decode($res->plaintext, ENT_QUOTES, "UTF-8");
        $movie['description'] = $title;
        $pos = strrpos($title, '/') + 1;
        if ($pos > 4)
            $title = trim(substr($title, $pos));

        $result = array();
        $res1 = preg_match_all('/\[S\d+/isU', $title, $result, PREG_OFFSET_CAPTURE);
        $res2 = preg_match_all('/\[[\d-x ,]+\]/isU', $title, $result, PREG_OFFSET_CAPTURE);
        if ($res1 || $res2) {//that's a series
            echo "\tskip $title\n";
            return false;
        }

        extractString($title, $movie);
        extractTranslate($title, $movie);
        return true;
    }

    function processTr($html){
        $movie = array();

        $curTr = array();
		foreach ($html->find('td') as $item)
			$curTr[] = trim(html_entity_decode($item->plaintext, ENT_QUOTES, "UTF-8"));
		if (count($curTr) == 4) {
		    $curTr[4] = $curTr[3];
		    $curTr[3] = $curTr[2];
		    $curTr[2] = 0;
		}
			
		$curTr[0] = str_replace(
		    array(" Янв "," Фев "," Мар "," Апр "," Май "," Июн "," Июл "," Авг "," Сен "," Окт "," Ноя "," Дек "), 
		    array(" Jan "," Feb "," Mar "," Apr "," May "," Jun "," Jul "," Aug "," Sep "," Oct "," Nov "," Dec "), 
		    $curTr[0] );
		$movie['added_tracker'] = strtotime($curTr[0]);
		if ( (time() - $movie['added_tracker']) / 3600 / 24 > ADDLINKSPASTDAYS)
		    return false;

        $movie['size'] = (float)$curTr[3];
        if (strpos($curTr[3], 'G'))
            $movie['size'] *= 1024;

        $preg_result = array();
    	$res = preg_match_all('/\d+/isu', $curTr[4], $preg_result);
    	if (!$preg_result || count($preg_result[0]) !=  2)
    	    return false;
    	$movie['seed'] = (int)$preg_result[0][0];
    	$movie['leech'] = (int)$preg_result[0][1];

		$res = $this->processTd($html->children(1), $movie);
            
		if (!$res)
		    return false;

        $this->result[] = $movie;
        return true;
    }
    
    function getRutorCallback($response, $info) {
        echo $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'] . "\n";
        if ($info['http_code'] != 200) {
            echo "\terror\n";
            return;
        }

		$html = str_get_html($response);
		if (!$html) {
		    echo "\tfailed to convert DOM\n";
		    return;
		}

		foreach($html->find('tr') as $row) {
		    $curTr = $row->find('td');
			if (count($curTr) == 5 || count($curTr) == 4) 
			    $this->processTr($row);
		}
		
		echo "\t " . count($this->result) . " new links found\n";
    }

    function load() {
        $this->result = array();

        \RollingCurl::$rc->get($this->link, null, null, array("callback"=>array($this, "getRutorCallback")) );
    }

    function getResult() {
        return (array)($this->result);
    }

}
?>