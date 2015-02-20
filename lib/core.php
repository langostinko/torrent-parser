<?php
    function qualityToRool($qual) {
        if (in_array($qual, 
            array("DVDSCR","TS","CAM","CAMRIP","HDTS","HDCAM","TELECINE","TC","",)
        )) return 0;
        return 1;
    }
    
    function translateQualityToStr($qual) {
        switch ($qual) {
            case 0:
                return "O";
            case 1:
                return "TS";
            case 2:
                return "L";
            case 3:
                return "P";
            case 4:
                return "D";
        }        
    }
    
    function translateQualityToRool($qual) {
        if (in_array($qual,
            array("ORIGINAL",)
        )) return 0;
        if (in_array($qual,
            array("ЗВУК С TS","ЗВУК С CAMRIP",)
        )) return 1;
        if (in_array($qual,
            array("L","L1","L2","A","ЕСАРЕВ","МАТВЕЕВ",)
        )) return 2;
        if (in_array($qual,
            array("P","P2","BAIBAKO",)
        )) return 3;
        return 4;
    }
    
    function checkTranslateQuality($qual, $rool) {
        return true;
    }

    function suggestIMDB($title) {
        //debug
        $title = strtolower($title);
        $title = str_replace(' ', '_', $title);
        $char = $title[0];
        $link = "http://sg.media-imdb.com/suggests/$char/$title.json";
        echo "$link\n";

        $file = file_get_contents($link);
        $pos = strlen($title) + 6;
        $file = substr($file, $pos, strlen($file) - $pos - 1);
        echo "'$file'";
        
        $json = json_decode($file, true);
        print_r($json);
    }

    function searchIMDB($title, &$movie){
        $title = strtolower($title);
        $link = "http://www.imdb.com/xml/find?json=1&nr=1&tt=on&q=" . urlencode($title);

        $file = file_get_contents($link);
        $json = json_decode($file, true);

        $vector = array('title_popular','title_exact','title_approx');
        $curDif = 2;
        foreach($vector as $type)
            if ($json and array_key_exists($type, $json))
                foreach ($json[$type] as $cur) {
                    $curYear = (int)substr($cur['description'],0,4);
                    $needYear = array_key_exists('year', $movie) ? (int)$movie['year'] : $curYear;
                    if (abs($curYear - $needYear) < $curDif) {
                        $movie['movie']['imdbid'] = $cur['id'];
                        $movie['movie']['title'] = html_entity_decode($cur['title'], ENT_QUOTES, "UTF-8");
                        //$movie['movie']['description'] = html_entity_decode($cur['description'], ENT_QUOTES, "UTF-8");
                        $movie['movie']['year'] = $curYear;
                        $curDif = abs($curYear - $needYear);
                    }
                }
        return ($curDif < 2);
    }

    function extractTranslate($str, &$movie){
    	$result = array();
    	$res = preg_match_all('/\| *[\W](лицензия|чистый звук|звук с ts|Звук с CAMRip|iTunes|BaibaKo)[\W]/isuU', $str.' ', $result);
    	if (!$result[0])
        	$res = preg_match_all('/\|.*[\W](Есарев|Матвеев)[\W]/isuU', $str.' ', $result);
    	if (!$result[0])
        	$res = preg_match_all('/\| *[\W](l|l1|l2|p|p2|D|A|А)[\W]/isuU', $str.' ', $result);
    	if ($result[0])
            $movie['translateQuality'] = mb_strtoupper($result[1][0], 'UTF-8');
    }

    function extractString($str, &$movie){
        $str = str_replace("."," ",$str);
        $str = str_replace("_"," ",$str);
    	
    	$result = array();
    	$res = preg_match_all('/\d\d\d\d/isuU', $str, $result, PREG_OFFSET_CAPTURE);
    	$pos = strlen($str);
    	if ($result[0]) {
    	    $movie['year'] = $result[0][0][0];
    	    $pos = $result[0][0][1];
    	}
    	
    	$res = preg_match_all('/[\W](dvdrip|dvdscr|hdrip|ts|tc|cam|brrip|webrip|bdrip|camrip|hdts|hdcam|hdtv|hdtvrip|telecine|web-dl|web-dlrip|bluray|bdremux|bd-remux)[\W]/isuU', $str.' ', $result, PREG_OFFSET_CAPTURE);
    	if ($result[0]) {
            $movie['quality'] = strtoupper($result[1][0][0]);
            $pos = min($pos, $result[1][0][1]);
    	}
    	
    	$res = preg_match_all('/french/isuU', $str, $result, PREG_OFFSET_CAPTURE);
    	if ($result[0] && $result[0][0][1])
                $pos = min($pos, $result[0][0][1]);

    	$left = substr($str, 0, $pos);
        $left = preg_replace('/[\[\]\(\)]/', '', $left);
        $left = trim($left);
        $movie['title_approx'] = html_entity_decode($left, ENT_QUOTES, "UTF-8");
    }
    
    function trySkipMovie($movie) {
        $imdbid=mysqli_real_escape_string($GLOBALS['mysqli'], $movie['imdbid']);
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE imdbid='$imdbid' AND updated > date_add(current_timestamp, interval -3 day)");
        if (!mysqli_num_rows($sqlresult))
            return false;
        $row = mysqli_fetch_assoc($sqlresult);
        if (!$row['title'])
            return false;
        $json = json_decode($row['description'], true);
        if (!$json or $json['Response'] == "False")
            return false;
        $img = "img/posters/$imdbid.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        if (array_key_exists("Poster", $json) && (!file_exists($realImg) or !filesize($realImg)) )
            return false;
        $img = "img/posters/{$imdbid}Ru.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        if (array_key_exists("PosterRu", $json) && (!file_exists($realImg) or !filesize($realImg)) )
            return false;
        return true;
    }

    function getKinopoiskLink($link) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate,sdch');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.kinopoisk.ru/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.122 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;

    }
    
    function getKinopoiskDesc($kinopoiskId, &$desc) {
        $response = getKinopoiskLink("http://www.kinopoisk.ru/film/".urlencode($kinopoiskId));
        if (!$response)
            return false;

        $result = array();
        $res = preg_match_all('/>([^<]+)<\/h1>/isU', $response, $result);
        if ($result && count($result[1]))
            $desc['titleRu'] = iconv('windows-1251', 'UTF-8', $result[1][0]);
            
        $result = array();
        $res = preg_match_all('/rating_ball\">([^<]+)<\/span>/isU', $response, $result);
        if ($result && count($result[1]))
            $desc['kinopoiskRating'] = iconv('windows-1251', 'UTF-8', $result[1][0]);

        $img = "img/posters/".$desc['imdbID']."Ru.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        $url = "http://st.kp.yandex.net/images/film_iphone/iphone360_$kinopoiskId.jpg";
        if ( !(file_exists($realImg) && filesize($realImg)) )
            file_put_contents($realImg, file_get_contents($url));
        if (file_exists($realImg) && filesize($realImg))
            $desc['PosterRu'] = $img;
        
        return true;
    }

    function getKinopoiskId($title, &$desc) {
		$response = getKinopoiskLink("http://www.kinopoisk.ru/index.php?first=no&what=&kp_query=".urlencode(iconv("windows-1251", "UTF-8", $title)));

        //search for HTTP 302
        $id = array();
        $res = preg_match_all('/Location: \/film\/(\d+)\//isu', $response, $id);
        if ($id && count($id[0]))
            $id = $id[1][0];
        else 
            $id = false;
        if ($id) {
            $desc['kinopoiskId'] = $id;
            return getKinopoiskDesc($id, $desc);
        }


        include_once(__DIR__.'/simple_html_dom.php');
		$html = str_get_html($response);

		foreach($html->find('div[class=element]') as $row) {
		    $pName = $row->find('p[class=name]',0);
		    
		    $link = $pName->find('a',0)->href;
            $id = array();
            $res = preg_match_all('/\/film\/(\d+)\//isu', $link, $id);
            if ($id && count($id[0]))
                $id = $id[1][0];
            else 
                $id = false;

            if ($id) {
    		    $name = $pName->find('a',0)->plaintext;
    		    $year = $pName->find('span',0)->plaintext;
    		    $rating = $row->find('div[class=rating]',0)->plaintext;
    
                $needYear = array_key_exists('Year', $desc) ? (int)$desc['Year'] : $year;
                if (abs($year - $needYear) <= 1) {
                    $desc['kinopoiskId'] = $id;
                    $desc['kinopoiskRating'] = $rating;
                    $desc['titleRu'] = iconv('windows-1251', 'UTF-8', $name);

                    $img = "img/posters/".$desc['imdbID']."Ru.jpg";
                    $realImg = dirname( __FILE__ ) . "/../$img";

                    $url = "http://st.kp.yandex.net/images/film_iphone/iphone360_$id.jpg";
                    if ( !(file_exists($realImg) && filesize($realImg)) )
                        file_put_contents($realImg, file_get_contents($url));
                    if (file_exists($realImg) && filesize($realImg))
                        $desc['PosterRu'] = $img;

                    return true;
                }
            }

		}
		return false;
    }   
    
    function getKinopoiskRating($kinopoiskId) {
        $kinopoiskId = (int)$kinopoiskId;
        $xml = simplexml_load_file("http://rating.kinopoisk.ru/$kinopoiskId.xml");
        return (string) $xml->kp_rating;
    }
    
    function addMovie(&$movie) {
        if (!$movie)
            return false;
        if (trySkipMovie($movie))
            return true;
        $imdbid=mysqli_real_escape_string($GLOBALS['mysqli'], $movie['imdbid']);
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE imdbid='$imdbid'");
        echo mysqli_error($GLOBALS['mysqli']);
        if (!mysqli_num_rows($sqlresult)) {
            mysqli_query($GLOBALS['mysqli'], "INSERT INTO movies(imdbid) VALUES('$imdbid')");
            echo mysqli_error($GLOBALS['mysqli']);
        } else {
            $row = mysqli_fetch_assoc($sqlresult);
            $movie['description'] = json_decode($row['description'], true);
        }

        $omdbapi = file_get_contents("http://www.omdbapi.com/?i=" . urlencode($movie['imdbid']));           
        $json = json_decode($omdbapi, true);
        if (!$json || $json['Response'] == "False" || !array_key_exists("Title", $json) )
            return false;

        $title = mysqli_real_escape_string($GLOBALS['mysqli'], $json['Title']);
        
        $kinRes = getKinopoiskId($movie['title'], $json);

        $img = "img/posters/$imdbid.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        if (array_key_exists('Poster', $json) && $json['Poster'] != 'N/A') {
            $url = $json['Poster'];
            if ( !(file_exists($realImg) && filesize($realImg)) ) {
                file_put_contents($realImg, file_get_contents($url));
            }
            $json['Poster'] = $img;
        } else
            unset($json['Poster']);
        $movie['description'] = array_merge((array)$movie['description'], $json);
        $description = mysqli_real_escape_string($GLOBALS['mysqli'], json_encode($movie['description'], JSON_UNESCAPED_UNICODE));

        //$year = (int)$movie['year'];
        if ($description) {
            mysqli_query($GLOBALS['mysqli'], "UPDATE movies SET title='$title', description='$description',updated=now() WHERE imdbid='$imdbid'");
            echo mysqli_error($GLOBALS['mysqli']);
            return true;
        }
        return false;
    }
    
    function trySkip($cur) {
        if (!is_array($cur) || !array_key_exists("link", $cur))
            return true;
        static $cache = false;
        if (!$cache) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT md5 FROM links WHERE updated > date_add(current_timestamp, interval -1 day)");
            while ($row = mysqli_fetch_assoc($sqlresult))
                $cache[$row['md5']] = true;
        }
        if ( array_key_exists(md5($cur['link']), $cache) && array_key_exists("seed", $cur) && array_key_exists("leech", $cur) ) {
            $seed = (int)$cur['seed'];
            $leech = (int)$cur['leech'];
            mysqli_query($GLOBALS['mysqli'], "UPDATE links SET seed=$seed, leech=$leech, updated=NOW() WHERE md5 = '" . md5($cur['link']) . "'");
            return true;
        }
        return array_key_exists(md5($cur['link']), $cache);
    }
    
    function addLink($cur) {
        if (!addMovie($cur['movie']))
            return false;
        $hash = md5($cur['link']);
        $link = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['link']);
        $imdbid = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['movie']['imdbid']);

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE md5 = '$hash'");
        echo mysqli_error($GLOBALS['mysqli']);
        if (!mysqli_num_rows($sqlresult)) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "INSERT INTO links(link,md5) VALUES('$link', '$hash')");
            echo mysqli_error($GLOBALS['mysqli']);
        }
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id FROM movies WHERE imdbid='$imdbid'");
        echo mysqli_error($GLOBALS['mysqli']);
        $id = mysqli_fetch_assoc($sqlresult);$id = (int)$id['id'];
        $description = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['description']);
        $quality = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['quality']);
        $translateQuality = array_key_exists('translateQuality',$cur)?$cur['translateQuality']:"";
        $size = (float)$cur['size'];
        $seed = (int)$cur['seed'];
        $leech = (int)$cur['leech'];
        mysqli_query($GLOBALS['mysqli'], "UPDATE links SET movieId=$id, description='$description', quality='$quality', translateQuality='$translateQuality', size=$size, seed=$seed, leech=$leech, updated=now() WHERE md5 = '$hash'");
        echo mysqli_error($GLOBALS['mysqli']);
    }
?>