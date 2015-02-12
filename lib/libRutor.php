<?php
include_once('lib.php');

class Rutor {

    public $result;
    
    function __construct() {
        $this->result = array();
    }

    function processTd($html, &$movie){
        $res = $html->find('a',1);
        if (!$res)
            return false;
        $movie['link'] = "http://alt.rutor.org".$res->href;
        if (trySkip($movie))
            return false;
        
        $title = html_entity_decode($res->plaintext, ENT_QUOTES, "UTF-8");
        $movie['description'] = $title;
        $pos = strrpos($title, '/') + 1;
        $title = trim(substr($title, $pos));

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
		if ( (time() - strtotime($curTr[0])) / 3600 / 24 > 120)
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

        include_once('simple_html_dom.php');
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

    function getRutor($link = "http://alt.rutor.org/browse/0/1/0/2/"){
        $this->result = array();

        \RollingCurl::$rc->get($link, null, null, array("callback"=>array($this, "getRutorCallback")) );
    }

}
?>