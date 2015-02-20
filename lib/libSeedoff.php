<?php
    namespace seedoff;

    include_once('lib.php');    
    
    $result = array();
    
    function processTd($html, &$movie){
        $res = $html->find('a',0);
        if (!$res)
            return false;
        
        $movie['link'] = "http://www.seedoff.net".$res->href;
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
        if ( (time() - strtotime($html->find('td',7)->plaintext) ) / 3600 / 24 > 180)
            return false;
            
        $movie = array();
		$res = processTd($html->children(1), $movie);
		if (!$res)
		    return false;
		    
        $curTr = array();
		foreach ($html->find('td') as $item)
			$curTr[] = trim(html_entity_decode($item->plaintext, ENT_QUOTES, "UTF-8"));

        $movie['size'] = (float)$curTr[6];
        if (strpos($curTr[6], 'G'))
            $movie['size'] *= 1024;

    	$movie['seed'] = (int)$curTr[8];
    	$movie['leech'] = (int)$curTr[9];
        global $result;
        $result[] = $movie;

        return true;
    }

    function getSeedoff($link = "http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=1"){
        echo "fetching $link\n";
        //$file = file_get_contents($link);
        global $result;
        $result = array();

        include_once('simple_html_dom.php');
		$html = file_get_html($link);
		if (!$html) {
		    echo "failed\n";
		    return $result;
		}

		foreach($html->find('tr') as $row) {
		    $curTr = $row->find('td');
			if (count($curTr) == 11)
			    processTr($row);
		}
		
		return $result;
    }
    
?>