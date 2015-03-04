<?php
include_once('lib.php');

class Pirate {
    
    public $result;

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
        $this->processDesc($res->plaintext, $movie);
        return true;
    }

    function processTr($html){
        $movie = array();

        $curTr = array();
		foreach ($html->find('td') as $item)
			$curTr[] = $item->plaintext;
		$movie['seed'] = (int)$curTr[2];
		$movie['leech'] = (int)$curTr[3];
		$movie['translateQuality'] = 'ORIGINAL';

		$res = $this->processTd($html->children(1), $movie);
		if (!$res)
		    return false;

        $this->result[] = $movie;
        return true;
    }
    
    function getPirateCallback($response, $info, $request) {
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

        $got = 0;
        $cnt = $request->cookie['cnt'];
		foreach($html->find('tr') as $row) {
		    $curTr = $row->find('td');
			if (count($curTr) == 4) {
			    ++$got;
			    $this->processTr($row);
			}
			if ($got >= $cnt)
			    break;
		}
		echo "\t " . count($this->result) . " new links found\n";
    }

    function getPirateBay($link = "http://thepiratebay.se/browse/201/0/7/0", $cnt = 50){
        $this->result = array();
        \RollingCurl::$rc->get($link, null, null, array("callback"=>array($this, "getPirateCallback"), "cnt"=>$cnt) );
    }
    
}
?>