<?php
    namespace pirate;

    include_once('lib.php');

    $result = array();

    function processDesc($str, &$movie){
        $str = html_entity_decode($str);
        $pos = strpos($str, "Size") + 5;
        $right = substr($str, $pos);
        $pos = strpos($right, ",");
        $right = substr($right, 0, $pos);
        $size = (float)substr($right, 0, strlen($right) - 4);
        $right = substr($right, strlen($right) - 3);
        if ($right[0]=='G')
            $size *= 1024;
        if ($size) {
            $movie['size'] = $size;
            return true;
        }
        return false;
    }

    function processTd($html, &$movie){
        $res = $html->find('div.detName a',0);
        if (!$res)
            return false;
        $link = "http://thepiratebay.se".$res->href;
        $movie['link'] = $link;
        if (trySkip($movie)) {
            return false;
        }

        $movie['description'] = html_entity_decode($res->plaintext, ENT_QUOTES, "UTF-8");
        extractString($res->plaintext, $movie);
        

        $res = $html->find('font.detDesc',0);
        if (!$res)
            return false;
        processDesc($res->plaintext, $movie);
        return true;
    }

    function processTr($html){
        $movie = array();
		$res = processTd($html->children(1), $movie);
		if (!$res)
		    return false;
        $curTr = array();
		foreach ($html->find('td') as $item)
			$curTr[] = $item->plaintext;
		$movie['seed'] = (int)$curTr[2];
		$movie['leech'] = (int)$curTr[3];
		$movie['translateQuality'] = 'ORIGINAL';
        global $result;
        $result[] = $movie;
        return true;
    }

    function getPirateBay($link = "http://thepiratebay.se/browse/201/0/7/0", $cnt = 50){
        echo "fetching $link\n";

        global $result;
        $result = array();

        include_once('simple_html_dom.php');

        $html = file_get_html($link);

        $got = 0;
		foreach($html->find('tr') as $row) {
		    $curTr = $row->find('td');
			if (count($curTr) == 4) {
			    ++$got;
			    processTr($row);
			}
			if ($got >= $cnt)
			    break;
		}

		return $result;
    }

?>