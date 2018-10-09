<?php
    function file_get_contents_curl($url) {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
    
        $data = curl_exec($ch);
        curl_close($ch);
    
        return $data;
    }

    function getImgFromLink($link) {
        $prefix = "img/trackers";
        if (strpos($link, RUTORROOT) !== false)
            return "$prefix/rutor.ico";
        if (strpos($link, "seedoff") !== false)
            return "$prefix/seedoff.ico";
        if (strpos($link, NNMROOT) !== false)
            return "$prefix/nnm-club.ico";
        if (strpos($link, "filmitorrent.org") !== false)
            return "$prefix/filmitorren.png";
        if (strpos($link, "ivi.ru") !== false)
            return "$prefix/ivi.png";
        if (strpos($link, "megogo") !== false)
            return "$prefix/megogo.png";
        if (strpos($link, "play.google.com") !== false)
            return "$prefix/google_play.png";
        if (strpos($link, "itunes.apple.com") !== false)
            return "$prefix/itunes.png";
        return "$prefix/torrent.ico";
    }

    function qualityToRool($qual) {
        if (in_array($qual, 
            array("DVDSCR","TS","CAM","CAMRIP","HDTS","HDCAM","TELECINE","TC","",)
        )) return 0;
        return 1;
    }
    
    function translateQualityToRool($qual) {
        if (in_array($qual,
            array("ORIGINAL",)
        )) return 0;
        if (in_array($qual,
            array("ЗВУК С TS","ЗВУК С CAMRIP",)
        )) return 1;
        if (in_array($qual,
            array("L","L1","L2","A","ЕСАРЕВ","МАТВЕЕВ","VO","SUB","AVO","BADBAJO","LAKEFILMS")
        )) return 2;
        if (in_array($qual,
            array("P","P2","BAIBAKO","MVO","HDREZKA STUDIO","JASKIER","NEWSTUDIO")
        )) return 3;
        if (in_array($qual,
            array("ЧИСТЫЙ ЗВУК","LINE",)
        )) return 4;
        return 5;
    }
    
    function checkTranslateQuality($qual, $rool) {
        return true;
    }

    function strtr_utf8($str, $from, $to) {
        $keys = array();
        $values = array();
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        return strtr($str, $mapping);
    }
    
    function translit_utf8($str) {
        return strtr_utf8($str, 
          "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÝßàáâãäåæçèéêëìíîïñòóôõöøùúûüýÿ",
          "AAAAAAACEEEEIIIINOOOOOOYSaaaaaaaceeeeiiiinoooooouuuuyy");
    }

    function searchIMDB($title, &$movie){
        $title = translit_utf8($title);
        $link = "http://www.omdbapi.com/?apikey=726aea6&type=movie&t=" . urlencode($title);
        if (array_key_exists('year', $movie)) {
            $link .= '&y=' . $movie['year'];
        }

        $file = file_get_contents_curl($link);
        $json = json_decode($file, true);
        if ($json && array_key_exists('Title', $json)) {
            $movie['movie']['imdbid'] = $json['imdbID'];
            $movie['movie']['title'] = $json['Title'];
            $img = "img/posters/".$json['imdbID'].".jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists('Poster', $json) && $json['Poster'] != 'N/A') {
                $url = $json['Poster'];
                unset($json['Poster']);
                if ( !(file_exists($realImg) && filesize($realImg)) )
                    file_put_contents($realImg, file_get_contents_curl($url));
                if (file_exists($realImg) && filesize($realImg))
                    $json['Poster'] = $img;
            } else
                unset($json['Poster']);
            $movie['movie']['description'] = array_key_exists('description', $movie['movie']) ? array_merge($movie['movie']['description'], $json) : $json;
            return true;
        }
        return false;
    }

    function getKinopoiskLink($link, $follow_location = 0) {
        global $logger;
        $response = "";
        $proxy = false;
        do {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow_location);
            curl_setopt($ch, CURLOPT_REFERER, KINOPOISKROOT);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.122 Safari/537.36');
            if ($proxy) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
                $logger->info("trying $link with proxy $proxy");
            }
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                if (strpos(@$info['url'], 'showcaptcha') !== false) {
                    $response = false;
                }
            } else {
                if (strpos(@$info['redirect_url'], 'showcaptcha') !== false) {
                    $reponse = false;
                }
            }
            if (!$response)
                $proxy = ProxyFinder::findProxy($link, $proxy);
            curl_close($ch);
        } while (!$response && $proxy);
        return $response;
    }
    
    function getKinopoiskMoviesList($userId) {
        $link = KINOPOISKROOT."/user/$userId/movies/list/perpage/200/";
        $response = getKinopoiskLink($link);
        
        include_once(__DIR__.'/simple_html_dom.php');
        $html = str_get_html($response);
        if (!$html)
            return false;

        $result = array();
        foreach($html->find('li[class=item]') as $row) {
            $movieId = array();
            $res = preg_match_all('/film_([^\/]+)/isu', $row->id, $movieId);
            if ($movieId && count($movieId[0]))
                array_push($result, (int)$movieId[1][0]);
        }
        
        return $result;
    }

    function searchKinopoisk($title, &$movie){
        $title = translit_utf8($title);
        $title = str_replace("'s", "", $title);
        $link = KINOPOISKROOT."/index.php?first=no&what=&kp_query=".urlencode($title);
        $response = getKinopoiskLink($link);

        //search for HTTP 302
        $id = array();
        $res = preg_match_all('/Location: \/film\/([^\/]+)\//isu', $response, $id);
        if ($id && count($id[0]))
            $id = $id[1][0];
        else 
            $id = false;
        if ($id) {
            $movie['movie']['kpid'] = $id;
            return true;
        }

        include_once(__DIR__.'/simple_html_dom.php');
        $html = str_get_html($response);
        if (!$html)
            return false;

        foreach($html->find('div[class=element]') as $row) {
            $pName = $row->find('p[class=name]',0);

            $link = $pName->find('a',0)->href;
            $id = array();
            $res = preg_match_all('/\/film\/([^\/]+)\//isu', $link, $id);
            if ($id && count($id[0]))
                $id = $id[1][0];
            else 
                $id = false;

            if ($id) {
                $name = html_entity_decode(iconv('windows-1251', 'UTF-8', $pName->find('a',0)->plaintext));
                if (strpos($name, "(сериал)") !== false) //skip series
                    continue;
                $year = $pName->find('span',0)->plaintext;
                $desc = array(
                    'kinopoiskId' => $id,
                    'titleRu' => $name,
                    'Year' => $year,
                    'kinopoiskRating' => @$row->find('div[class=rating]',0)->plaintext,
                );

                $needYear = array_key_exists('year', $movie) ? (int)$movie['year'] : $year;
                if (abs($year - $needYear) <= 2) {
                    $movie['movie']['kpid'] = $id;
                    $movie['movie']['titleRu'] = $name;
                    $movie['movie']['yearRu'] = $year;
                    $movie['movie']['description'] = array_key_exists('description', $movie['movie']) ? array_merge($movie['movie']['description'], $desc) : $desc;

                    $img = "img/posters/{$id}Ru.jpg";
                    $realImg = dirname( __FILE__ ) . "/../$img";
                    preg_match_all('/\d+$/isu', $id, $shortid);
                    $shortid = @$shortid[0][0];
                    $url = "http://st.kp.yandex.net/images/film_iphone/iphone360_$shortid.jpg";
                    if ( !(file_exists($realImg) && filesize($realImg) > 4000) )
                        file_put_contents($realImg, file_get_contents_curl($url));
                    if (file_exists($realImg) && filesize($realImg) > 4000)
                        $movie['movie']['description']['PosterRu'] = $img;

                    return true;
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
        $str = str_replace(" c ", " с ", $str);
        $str = preg_replace("/3(..)/", "З$1", $str);
        $result = array();
        $res = preg_match_all('/(\||\[| l ) *(лицензия|чистый звук|звук с ts|Звук с CAMRip|iTunes|line)[\W]/isuU', $str.' ', $result);
        if ($result[0]) {
            $movie['translateQuality'] = mb_strtoupper($result[2][0], 'UTF-8');
            return;
        }
        $res = preg_match_all('/(\||\[| l ).*(Есарев|Матвеев|BadBajo|Jaskier|LakeFilms|NewStudio|HDrezka Studio|BaibaKo)[\W]/isuU', $str.' ', $result);
        if ($result[0]) {
            $movie['translateQuality'] = mb_strtoupper($result[2][0], 'UTF-8');
            return;
        }
        $res = preg_match_all('/(\||\[| l ) *(l|l1|l2|p|p2|D|A|А|sub|vo|mvo|avo)[\W]/isuU', $str.' ', $result);
        if ($result[0]) {
            $movie['translateQuality'] = mb_strtoupper($result[2][0], 'UTF-8');
            return;
        }
        $movie['translateQuality'] = "";
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
        static $cacheOld = false;
        static $cacheFresh = false;
        if (!$cacheFresh) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id, imdbid, kpid FROM movies WHERE updated > date_add(current_timestamp, interval -".UPDATEMOVIESEVERYDAYS." day)");
            while ($row = mysqli_fetch_assoc($sqlresult)) {
                $cacheFresh[$row['imdbid']] = $row['id'];
                $cacheFresh[$row['kpid']] = $row['id'];
            }
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id, imdbid, kpid FROM movies WHERE updated <= date_add(current_timestamp, interval -".UPDATEMOVIESEVERYDAYS." day)");
            while ($row = mysqli_fetch_assoc($sqlresult)) {
                $cacheOld[$row['imdbid']] = $row['id'];
                $cacheOld[$row['kpid']] = $row['id'];
            }
        }

        $row = false;
        $fresh = false;
        $idTypes = array("kpid", "imdbid");
        foreach ($idTypes as $idName) {
            if (array_key_exists($idName, $movie) && $movie[$idName] && array_key_exists($movie[$idName], $cacheFresh)) {
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id=" . $cacheFresh[$movie[$idName]]);
                $row = mysqli_fetch_assoc($sqlresult);
                $fresh = true;
                break;
            }
            if (array_key_exists($idName, $movie) && $movie[$idName] && array_key_exists($movie[$idName], $cacheOld)) {
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id=" . $cacheOld[$movie[$idName]]);
                $row = mysqli_fetch_assoc($sqlresult);
                $fresh = false;
                break;
            }
        }
        if (!$row)
            return "row not found in mysql cache";
        if (!$fresh)
            return "scheduled update";

        foreach ($idTypes as $idName)
            if (array_key_exists($idName, $movie) && $movie[$idName] && !$row[$idName])
                return "no $idName";

        $json = json_decode($row['description'], true);
        if (!$json)
            return "no json description";
        if (!$row['search'])
            return "no search column";

        if (array_key_exists("imdbid", $movie) && $movie['imdbid']) {
            $img = "img/posters/{$movie['imdbid']}.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists("Poster", $json) && (!file_exists($realImg) or !filesize($realImg)) )
                return "poster '$realImg' not downloaded";
        }
        if (array_key_exists("kpid", $movie) && $movie['kpid']) {
            $img = "img/posters/{$movie['kpid']}Ru.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists("PosterRu", $json) && (!file_exists($realImg) or !filesize($realImg)) )
                return "poster '$realImg' not downloaded";
        }
        $movie['id'] = $row['id'];
        return 0;
    }
    
    function getKinopoiskDesc($kpid, &$desc) {
        $response = getKinopoiskLink(KINOPOISKROOT."/film/".urlencode($kpid)."/", true);
        if (!$response)
            return false;

        include_once(__DIR__.'/simple_html_dom.php');
        $html = str_get_html($response);
        if (!$html || !$html->find('table[class=info]',0))
            return false;

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
        
        $desc['Released'] = $desc['Year']."0101";
        $dvd = $html->find('div[data-ical-type=dvd]',0);
        if ($dvd)
            $desc['Released'] = $desc['ReleaseDVD'] = iconv('windows-1251', 'UTF-8', $dvd->getAttribute("data-date-premier-start-link"));
            
        $prem = $html->find('div[data-ical-type=world]',0);
        if (!$prem)
            $prem = $html->find('div[data-ical-type=rus]',0);
        if ($prem)
            $desc['Released'] = iconv('windows-1251', 'UTF-8', $prem->getAttribute("data-date-premier-start-link"));
            
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
        if ( !(file_exists($realImg) && filesize($realImg) > 4000) )
            file_put_contents($realImg, file_get_contents_curl($url));
        if (file_exists($realImg) && filesize($realImg) > 4000)
            $desc['PosterRu'] = $img;            
        
        return true;
    }

    function getIMDBDesc($imdbid, &$desc) {
        $omdbapi = file_get_contents_curl("http://www.omdbapi.com/?apikey=726aea6&i=" . urlencode($imdbid));
        $json = json_decode($omdbapi, true);
        if ($json && array_key_exists("Title", $json)) {
            $img = "img/posters/$imdbid.jpg";
            $realImg = dirname( __FILE__ ) . "/../$img";
            if (array_key_exists('Poster', $json) && $json['Poster'] != 'N/A') {
                $url = $json['Poster'];
                unset($json['Poster']);
                if ( !(file_exists($realImg) && filesize($realImg)) )
                    file_put_contents($realImg, file_get_contents_curl($url));
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
    
    function addMovie(&$movie, $force = false) {
        global $logger;
        if (!$movie)
            return "illegal argument (null)";
        if (!$force && trySkipMovie($movie) === 0)
            return 0;

        $row = false;
        $idTypes = array("kpid", "imdbid");
        foreach ($idTypes as $idName) 
            if (array_key_exists($idName, $movie) && $movie[$idName]) {
                $id = mysqli_real_escape_string($GLOBALS['mysqli'], $movie[$idName]);
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE $idName='$id'");
                if (mysqli_errno($GLOBALS['mysqli']))
                    $logger->error(mysqli_error($GLOBALS['mysqli']));
                if (mysqli_num_rows($sqlresult)) {
                    $row = mysqli_fetch_assoc($sqlresult);
                    break;
                }
            }
        if (!$row)
            $row['description'] = "{}";
        if (!@$movie['description']) {
            $movie['description'] = array();
        }
        $movie['description'] = array_merge(json_decode($row['description'], true), $movie['description']);

        $q = "UPDATE movies SET updated=now()";            

        if (!(array_key_exists("kpid", $movie) && $movie['kpid']) && is_array($movie['description']) && array_key_exists("kinopoiskId", $movie['description']) )
            $movie['kpid'] = $movie['description']['kinopoiskId'];

        if (array_key_exists("imdbid", $movie) && $movie['imdbid']) {
            $q .= ", imdbid='{$movie['imdbid']}'";
//            getIMDBDesc($movie['imdbid'], $movie['description']);
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
            if (mysqli_errno($GLOBALS['mysqli']))
                $logger->error(mysqli_error($GLOBALS['mysqli']));
            return 0;
        }
        return "title, description, released or year not found : " . print_r($movie, true);
    }
    
    function trySkip($cur) {
        global $logger;
        if (!is_array($cur) || !array_key_exists("link", $cur))
            return true;
        static $cache = false;
        static $cachedUpdate = array();
        if (!$cache) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT md5 FROM links");
            if (mysqli_errno($GLOBALS['mysqli']))
                $logger->error(mysqli_error($GLOBALS['mysqli']));
            while ($row = mysqli_fetch_assoc($sqlresult))
                $cache[$row['md5']] = true;
        }
        if ( array_key_exists(md5($cur['link']), $cache) ) {
            if (array_key_exists("seed", $cur)
                && array_key_exists("leech", $cur)
                && array_key_exists("size", $cur)
                && array_key_exists("translateQuality", $cur)
                && array_key_exists("description", $cur)
            ) {
                $seed = (int)$cur['seed'];
                $leech = (int)$cur['leech'];
                $size = (int)$cur['size'];
                $translateQuality = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['translateQuality']);
                $description = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['description']);
                $cachedUpdate[] = array(md5($cur['link']), $seed, $leech, $size, $translateQuality, $description);
                if (count($cachedUpdate) >= 10) {
                    $query = "INSERT INTO links (md5,seed,leech,size,translateQuality,description) VALUES ";
                    for ($i = 0; $i < count($cachedUpdate); $i++) {
                        $query .= "(\""  . $cachedUpdate[$i][0]
                            . "\", " . $cachedUpdate[$i][1]
                            . ", " . $cachedUpdate[$i][2]
                            . ", " . $cachedUpdate[$i][3]
                            . ", '" . $cachedUpdate[$i][4] . "'"
                            . ", '" . $cachedUpdate[$i][5] . "'"
                            . ")";
                        if ($i != count($cachedUpdate) - 1)
                            $query .= ", ";
                    }
                    $query .= " ON DUPLICATE KEY UPDATE "
                        ."seed=VALUES(seed)"
                        .",leech=VALUES(leech)"
                        .",size=VALUES(size)"
                        .",translateQuality=VALUES(translateQuality)"
                        .",description=VALUES(description)"
                        .",updated=now()";
                    $cachedUpdate = array();
                    mysqli_query($GLOBALS['mysqli'], $query);
                    if (mysqli_errno($GLOBALS['mysqli']))
                        $logger->error(mysqli_error($GLOBALS['mysqli']));
                }
                return true;
            } else {
                $logger->warning("link is not full: " . print_r($cur, true));
            }
        }
        return array_key_exists(md5($cur['link']), $cache);
    }
    
    function addLink($cur) {
        global $logger;
        if ( ($res = addMovie($cur['movie'])) !== 0)
            return "could not add movie: $res";
        $hash = md5($cur['link']);
        $link = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['link']);

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE md5 = '$hash'");
        if (mysqli_errno($GLOBALS['mysqli']))
            $logger->error(mysqli_error($GLOBALS['mysqli']));
        if (!mysqli_num_rows($sqlresult)) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "INSERT INTO links(link,md5) VALUES('$link', '$hash')");
            if (mysqli_errno($GLOBALS['mysqli']))
                $logger->error(mysqli_error($GLOBALS['mysqli']));
        }

        $id = $cur['movie']['id'];
        $description = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['description']);
        $quality = mysqli_real_escape_string($GLOBALS['mysqli'], $cur['quality']);
        $translateQuality = mysqli_real_escape_string($GLOBALS['mysqli'], array_key_exists('translateQuality',$cur)?$cur['translateQuality']:"");
        $type = array_key_exists("type", $cur)?((int)$cur['type']):0;
        $size = (float)$cur['size'];
        $seed = (int)$cur['seed'];
        $leech = (int)$cur['leech'];
        if (!array_key_exists('added_tracker', $cur))
            $cur['added_tracker'] = time();
        $added_tracker = date("Y-m-d H:i:s",(int)$cur['added_tracker']);
        mysqli_query($GLOBALS['mysqli'], "UPDATE links SET movieId=$id, description='$description', quality='$quality', translateQuality='$translateQuality', type=$type, size=$size, seed=$seed, leech=$leech, updated=now(), added_tracker='$added_tracker' WHERE md5 = '$hash'");
        if (mysqli_errno($GLOBALS['mysqli']))
            $logger->error(mysqli_error($GLOBALS['mysqli']));
        return 0;
    }
?>
