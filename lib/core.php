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
            array("ORIGINAL","SUB",)
        )) return 0;
        if (in_array($qual,
            array("ЗВУК С TS","ЗВУК С CAMRIP",)
        )) return 1;
        if (in_array($qual,
            array("L","L1","L2","A","ЕСАРЕВ","МАТВЕЕВ","VO")
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

    function searchKinopoisk($title, &$movie){
        $link = "http://www.kinopoisk.ru/index.php?first=no&what=&kp_query=".urlencode($title);
        $response = getKinopoiskLink($link);

        //search for HTTP 302
        $id = array();
        $res = preg_match_all('/Location: \/film\/(\d+)\//isu', $response, $id);
        if ($id && count($id[0]))
            $id = $id[1][0];
        else 
            $id = false;
        if ($id) {
            $movie['movie']['kpid'] = $id;
            return;
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
                $rating = @$row->find('div[class=rating]',0)->plaintext;

                $needYear = array_key_exists('year', $movie) ? (int)$movie['year'] : $year;
                if (abs($year - $needYear) <= 1) {
                    $movie['movie']['kpid'] = $id;
                    return;
                }
            }

        }
        return false;        
    }

    function getIds($title, &$movie) {
        searchIMDB($title, $movie);
        searchKinopoisk($title, $movie);
    }

    function extractTranslate($str, &$movie){
    	$result = array();
    	$res = preg_match_all('/[\|\[] *(лицензия|чистый звук|звук с ts|Звук с CAMRip|iTunes|BaibaKo|line)[\W]/isuU', $str.' ', $result);
    	if (!$result[0])
        	$res = preg_match_all('/[\|\[].*(Есарев|Матвеев)[\W]/isuU', $str.' ', $result);
    	if (!$result[0])
        	$res = preg_match_all('/[\|\[] *(l|l1|l2|p|p2|D|A|А|sub|vo)[\W]/isuU', $str.' ', $result);
    	if ($result[0])
            $movie['translateQuality'] = mb_strtoupper($result[1][0], 'UTF-8');
    }

    function extractString($str, &$movie){
        $str = str_replace("."," ",$str);
        $str = str_replace("_"," ",$str);
    	
    	$result = array();
    	$res = preg_match_all('/\d\d\d\d/isuU', $str, $result, PREG_OFFSET_CAPTURE);
    	$pos = strlen($str);
    	if ($result[0])
            foreach ($result[0] as $value) 
                if ($value[0] > 1920 && $value[0] < 2050) {
            	    $movie['year'] = $value[0];
            	    $pos = $value[1];
                }
    	$res = preg_match_all('/[\W](dvdrip|dvdscr|hdrip|нdrip|ts|tc|cam|brrip|webrip|bdrip|camrip|hdts|hdcam|hdtv|hdtvrip|telecine|web-dl|web-dlrip|bluray|bdremux|bd-remux)[\W]/isuU', $str.' ', $result, PREG_OFFSET_CAPTURE);
    	if ($result[0]) {
            $movie['quality'] = strtoupper($result[1][0][0]);
            $movie['quality'] = str_replace("НDRIP", "HDRIP", $movie['quality']);//н as h
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
    
    function trySkipMovie(&$movie) {
        $row = false;
        $idTypes = array("imdbid", "kpid");
        foreach ($idTypes as $idName) 
            if (array_key_exists($idName, $movie) && $movie[$idName]) {
                $id = mysqli_real_escape_string($GLOBALS['mysqli'], $movie[$idName]);
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE $idName='$id' AND updated > date_add(current_timestamp, interval -3 day)");
                if (mysqli_num_rows($sqlresult)) {
                    $row = mysqli_fetch_assoc($sqlresult);
                    break;
                }
            }
        if (!$row)
            return false;

        $json = json_decode($row['description'], true);
        if (!$json)
            return false;
        if (!$row['search'])
            return false;

        if (array_key_exists("imdbid", $movie) && $movie['imdbid']) {
            $img = "img/posters/{$movie['imdbid']}.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists("Poster", $json) && (!file_exists($realImg) or !filesize($realImg)) )
                return false;
        }
        if (array_key_exists("kpid", $movie) && $movie['kpid']) {
            $img = "img/posters/{$movie['kpid']}Ru.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists("PosterRu", $json) && (!file_exists($realImg) or !filesize($realImg)) )
                return false;
        }
        $movie['id'] = $row['id'];
        return true;
    }
    
    function getKinopoiskRating($kinopoiskId) {
        $kinopoiskId = (int)$kinopoiskId;
        $xml = simplexml_load_file("http://rating.kinopoisk.ru/$kinopoiskId.xml");
        return (string) $xml->kp_rating;
    }
    
    function getKinopoiskDesc($kpid, &$desc) {
        $response = getKinopoiskLink("http://www.kinopoisk.ru/film/".urlencode($kpid));
        if (!$response)
            return false;

        include_once(__DIR__.'/simple_html_dom.php');
        $html = str_get_html($response);

        foreach($html->find('table[class=info]',0)->find("tr") as $row) {
            $key = trim(iconv('windows-1251', 'UTF-8', $row->find('td',0)->plaintext), "., ");
            $value = trim(iconv('windows-1251', 'UTF-8', $row->find('td',1)->plaintext), "., ");
            $value = html_entity_decode($value);
            $value = preg_replace('!\s+!', ' ', $value);
            $key = str_replace(array("год"), array("Year"), $key);
            $desc[$key] = $value;
        }
        if (array_key_exists("жанр", $desc))
            $desc['жанр'] = trim(str_replace("слова", "", $desc['жанр']),",. ");

        $desc['kinopoiskId'] = $kpid;
        $desc['titleRu'] = html_entity_decode(trim(iconv('windows-1251', 'UTF-8', $html->find('h1[class=moviename-big]',0)->plaintext), "., "), ENT_QUOTES, "UTF-8");
        $desc['plotRu'] = html_entity_decode(iconv('windows-1251', 'UTF-8', $html->find('div[itemprop=description]',0)->plaintext), ENT_QUOTES, "UTF-8");
        $desc['kinopoiskRating'] = iconv('windows-1251', 'UTF-8', $html->find('span[class=rating_ball]',0)->plaintext);
        $prem = $html->find('td[id=div_world_prem_td2]',0);
        if (!($prem && $prem->find('div[class=prem_ical]',0)))
            $prem = $html->find('td[id=div_rus_prem_td2]',0);
        if ($prem && $prem->find('div[class=prem_ical]',0))
            $desc['Released'] = iconv('windows-1251', 'UTF-8', $prem->find('div[class=prem_ical]',0)->getAttribute("data-date-premier-start-link"));
        $act = $html->find('div[id=actorList]',0);
        if ($act && $act->find('ul',0)) {
            $act = $act->find('ul',0);
            $desc['актеры'] = "";
            foreach ($act->find('li[itemprop=actors]') as $actor)
                $desc['актеры'] .= iconv('windows-1251', 'UTF-8', $actor->plaintext) . ", ";
            $desc['актеры'] = trim($desc['актеры'], "., ");
        }

        $img = "img/posters/{$kpid}Ru.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        $url = "http://st.kp.yandex.net/images/film_iphone/iphone360_$kpid.jpg";
        if ( !(file_exists($realImg) && filesize($realImg)) )
            file_put_contents($realImg, file_get_contents($url));
        if (file_exists($realImg) && filesize($realImg))
            $desc['PosterRu'] = $img;            
        
        return true;
    }

    function getIMDBDesc($imdbid, &$desc) {
        $omdbapi = file_get_contents("http://www.omdbapi.com/?i=" . urlencode($imdbid));           
        $json = json_decode($omdbapi, true);
        if ($json && array_key_exists("Title", $json)) {
            $img = "img/posters/$imdbid.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists('Poster', $json) && $json['Poster'] != 'N/A') {
                $url = $json['Poster'];
                unset($json['Poster']);
                if ( !(file_exists($realImg) && filesize($realImg)) )
                    file_put_contents($realImg, file_get_contents($url));
                if (file_exists($realImg) && filesize($realImg))
                    $json['Poster'] = $img;
            } else
                unset($json['Poster']);
            $desc = array_merge(@$desc, $json);
            return true;
        }
        return false;
    }

    function generateSearchTags($desc) {
        $resutl = array();
        $keys = array("Title", "Year", "Genre", "Director", "Writer", "Actors", "Country", 
            "titleRu", "страна", "режиссер", "сценарий", "продюсер", "жанр", "актеры");
        return implode(";", array_intersect_key($desc, array_flip($keys)));
    }
    
    function addMovie(&$movie) {
        if (!$movie)
            return false;
        if (trySkipMovie($movie))
            return true;

        $row = false;
        $idTypes = array("imdbid", "kpid");
        foreach ($idTypes as $idName) 
            if (array_key_exists($idName, $movie) && $movie[$idName]) {
                $id = mysqli_real_escape_string($GLOBALS['mysqli'], $movie[$idName]);
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE $idName='$id'");
                echo mysqli_error($GLOBALS['mysqli']);
                if (mysqli_num_rows($sqlresult)) {
                    $row = mysqli_fetch_assoc($sqlresult);
                    break;
                }
            }
        if (!$row)
            $row['description'] = "";
        $movie['description'] = json_decode($row['description'], true);
        if (!$movie['description'])
            $movie['description'] = array();

        $q = "UPDATE movies SET updated=now()";            

        if (!(array_key_exists("kpid", $movie) && $movie['kpid']) && is_array($movie['description']) && array_key_exists("kinopoiskId", $movie['description']) )
            $movie['kpid'] = $movie['description']['kinopoiskId'];

        if (array_key_exists("imdbid", $movie) && $movie['imdbid']) {
            $q .= ", imdbid='{$movie['imdbid']}'";
            getIMDBDesc($movie['imdbid'], $movie['description']);
        }

        if (array_key_exists("kpid", $movie) && $movie['kpid']) {
            $q .= ", kpid='{$movie['kpid']}'";
            getKinopoiskDesc($movie['kpid'], $movie['description']);
        }
        
        $title = false;
        if (is_array($movie['description']) && array_key_exists('Title', $movie['description']))
            $title = $movie['description']['Title'];
        if (is_array($movie['description']) && array_key_exists('titleRu', $movie['description']))
            $title = $movie['description']['titleRu'];
        $description = mysqli_real_escape_string($GLOBALS['mysqli'], json_encode($movie['description'], JSON_UNESCAPED_UNICODE));

        if ($title && $description 
            && array_key_exists("Released", $movie['description']) 
            && array_key_exists("Year", $movie['description'])) {
            if (!array_key_exists('id', $row)) {
                mysqli_query($GLOBALS['mysqli'], "INSERT INTO movies(id) VALUES(NULL)");            
                $row['id'] = $GLOBALS['mysqli']->insert_id;
            }
            $id = $row['id'];
            $movie['id'] = $id;

            $search = generateSearchTags($movie['description']);
            $search = mysqli_real_escape_string($GLOBALS['mysqli'], $search);

            $q .= ", title='$title', description='$description', search='$search'";
            mysqli_query($GLOBALS['mysqli'],  "$q WHERE id=$id");
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

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE md5 = '$hash'");
        echo mysqli_error($GLOBALS['mysqli']);
        if (!mysqli_num_rows($sqlresult)) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "INSERT INTO links(link,md5) VALUES('$link', '$hash')");
            echo mysqli_error($GLOBALS['mysqli']);
        }

        $id = $cur['movie']['id'];
        $description = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['description']);
        $quality = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['quality']);
        $translateQuality = array_key_exists('translateQuality',$cur)?$cur['translateQuality']:"";
        $size = (float)$cur['size'];
        $seed = (int)$cur['seed'];
        $leech = (int)$cur['leech'];
        if (!array_key_exists('added_tracker', $cur))
            $cur['added_tracker'] = time();
        $added_tracker = date("Y-m-d H:i:s",(int)$cur['added_tracker']);
        mysqli_query($GLOBALS['mysqli'], "UPDATE links SET movieId=$id, description='$description', quality='$quality', translateQuality='$translateQuality', size=$size, seed=$seed, leech=$leech, updated=now(), added_tracker='$added_tracker' WHERE md5 = '$hash'");
        echo mysqli_error($GLOBALS['mysqli']);
        return true;
    }
?>