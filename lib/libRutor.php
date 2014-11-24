<?php
    namespace rutor;

    include_once('lib.php');

    $result = array();

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
		$res = processTd($html->children(1), $movie);
		if (!$res)
		    return false;

        $curTr = array();
		foreach ($html->find('td') as $item)
			$curTr[] = trim(html_entity_decode($item->plaintext, ENT_QUOTES, "UTF-8"));

        $movie['size'] = (float)$curTr[3];
        if (strpos($curTr[3], 'G'))
            $movie['size'] *= 1024;

        $result = array();
    	$res = preg_match_all('/\d+/isu', $curTr[4], $result);
    	if (!$result || count($result[0]) !=  2)
    	    return false;
    	$movie['seed'] = (int)$result[0][0];
    	$movie['leech'] = (int)$result[0][1];
        global $result;
        $result[] = $movie;
        return true;
    }

    function getRutor($link = "http://alt.rutor.org/browse/0/1/0/2/"){
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
			if (count($curTr) == 5) 
			    processTr($row);
		}
		
		return $result;
    }
?>