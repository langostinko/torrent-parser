<?php
    include_once(__DIR__."/pass.php");
    $VKserverToken = "";
    
    function getVKServerToken() {
        $data = array('client_id' => \pass\VK::$client_id,
                      'client_secret' => \pass\VK::$client_secret,
                      'grant_type' => 'client_credentials');
        $result = file_get_contents("https://oauth.vk.com/access_token?".http_build_query($data));
        $result = json_decode($result, true);
        return $result['access_token'];
    }

    function getVKName($vkid, $token) {
        $data = array('user_ids'=>$vkid,
                      'fields'=>'photo_100',
                      'access_token'=>$token);
        $result = file_get_contents("https://api.vk.com/method/users.get?".http_build_query($data));
        $result = json_decode($result, true);
        if (array_key_exists('response', $result)) {
            $result = $result["response"][0];
            $fname = mysqli_real_escape_string($result['first_name']);
            $lname = mysqli_real_escape_string($result['last_name']);
            $photo = mysqli_real_escape_string($result['photo_100']);
            mysql_query("UPDATE users SET login='$fname', lastName='$lname', photo='$photo' WHERE vkid=$vkid");
            return $fname;
        }
        return "user";
    }
    
    function sendVKNotification($vkid, $message) {
        if (!$VKserverToken)
            $VKserverToken = getVKServerToken();
        $data = array('user_ids'=>$vkid,
                      'message'=>$message,
                      'access_token'=>$VKserverToken,
                      'client_secret' => \pass\VK::$client_secret);
        $result = file_get_contents("https://api.vk.com/method/secure.sendNotification?".http_build_query($data));
        return $result;
    }
    
    function vkAuth(){
        //vk auth
        if (array_key_exists("code", $_GET)) {
            $data = array('client_id' => \pass\VK::$client_id,
                          'client_secret' => \pass\VK::$client_secret,
                          'code'=>$_GET['code'],
                          'redirect_uri'=>\pass\VK::$redirect_uri);
            $result = file_get_contents("https://oauth.vk.com/access_token?".http_build_query($data));
            $result = json_decode($result, true);
            if (array_key_exists('access_token', $result)) {
                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE vkid=" . $result['user_id']);
                if (!mysqli_num_rows($sqlresult))
                    mysqli_query($GLOBALS['mysqli'], "INSERT INTO users (vkid) VALUES(" . $result['user_id'] . ")");
                $expires = time() + $result['expires_in'];
                mysqli_query($GLOBALS['mysqli'], "UPDATE users SET expires=FROM_UNIXTIME($expires), token='" . $result['access_token'] . "' WHERE vkid=" . $result['user_id']);

                $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE vkid=" . $result['user_id']);
                $user = mysqli_fetch_assoc($sqlresult);
                if (!$user['login']) {
                    $user['login'] = getVKName($result['user_id'], $result['access_token']);
                }
                $_SESSION['user'] = $user;
                $_SESSION['expires'] = $expires;
                $_SESSION['showSettings'] = true;
                header("Location: http://".$_SERVER[HTTP_HOST]);
            }
            return 0;
        }        
    }

    function setSettings(&$user, $settings) {
        $userId = $user['id'];
        $quality = $user['quality'] = $settings['quality'];
        $minRating = $user['minRating'] = $settings['minRating'];
        $maxDaysDif = $user['maxDaysDif'] = $settings['maxDaysDif'];
        $minVotes = $user['minVotes'] = $settings['minVotes'];
        $translateQuality = $user['translateQuality'] = $settings['translateQuality'];
        if ($userId != 3)
            mysqli_query($GLOBALS['mysqli'], "UPDATE users SET quality=$quality, minRating=$minRating, maxDaysDif=$maxDaysDif, minVotes=$minVotes, translateQuality=$translateQuality WHERE id=$userId");
        $_SESSION["user"] = $user;
    }

    function ignoreMovie($userId, $movieId) {
        if ($userId == 2 || $userId == 3)
            return false;
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id FROM userignore WHERE userId=$userId AND movieId=$movieId");
        if (!mysqli_num_rows($sqlresult))
            mysqli_query($GLOBALS['mysqli'], "INSERT INTO userignore(userId,movieId) VALUES($userId, $movieId)");
    }
    
    function unIgnoreMovie($userId, $movieId) {
        if ($userId == 2 || $userId == 3)
            return false;
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "DELETE FROM userignore WHERE userId=$userId AND movieId=$movieId");
    }


    function Login($guestLogin = "wise guest")
    {
        //check if logined already
        if (!empty($_SESSION["user"]) && array_key_exists("expires", $_SESSION) && ($_SESSION["expires"] > time()) )
            return true;
        
        //login as guest by default
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE login='$guestLogin'" );
        $_SESSION['user'] = mysqli_fetch_assoc($sqlresult);
        return false;
    }

    function auth() {
        $result = array();
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="use guest:guest"');
        } else {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE login='".$_SERVER['PHP_AUTH_USER']."'" );
            if ($sqlresult && mysqli_num_rows($sqlresult)) {
                $row = mysqli_fetch_assoc($sqlresult);
                if (md5($_SERVER['PHP_AUTH_PW']) == $row['pass'])
                    $result = $row;
            }
        }  
        if (!$result) {
            header('HTTP/1.0 401 Unauthorized');
            echo "bad password\n";
            exit(0);
        }
        return $result;
    }

    function qualityToRool($qual) {
        if (in_array($qual, 
            array("DVDSCR","TS","CAM","CAMRIP","HDTS","HDCAM","TELECINE","TC",)
        )) return 0;
        return 1;
    }
    
    function translateQualityToRool($qual) {
        if (in_array($qual,
            array("ORIGINAL",)
        )) return 0;
        if (in_array($qual,
            array("L1","L2",)
        )) return 1;
        if (in_array($qual,
            array("P","P2","BAIBAKO",)
        )) return 2;
        return 3;
    }
    
    function checkTranslateQuality($qual, $rool) {
        return true;
    }
    
    function connect(){
        global $mysqli;
    	$host = \pass\SQL::$host;
    	$user = \pass\SQL::$user;
    	$pwd = \pass\SQL::$pwd;
    	$base = \pass\SQL::$base;
    	$mysqli = mysqli_connect($host, $user, $pwd);
    	if (!$mysqli) 
    		die("Connection error: " . mysqli_error());
    	// переменные для работы базы данных
    	mysqli_select_db ($mysqli, $base) or die ("Не могу соединиться с базой данных. Ошибка: " . mysql_error());
    	mysqli_set_charset($mysqli, 'utf8');
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
        //echo "$link\n";

        $file = file_get_contents("http://www.imdb.com/xml/find?json=1&nr=1&tt=on&q=" . urlencode($title));
        $json = json_decode($file, true);

        $vector = array('title_popular','title_exact','title_approx');
        foreach($vector as $type)
            if ($json and array_key_exists($type, $json))
                foreach ($json[$type] as $cur) {
                    $curYear = (int)substr($cur['description'],0,4);
                    $needYear = array_key_exists('year', $movie) ? (int)$movie['year'] : $curYear;
                    if (abs($curYear - $needYear) <= 1) {
                        $movie['movie']['imdbid'] = $cur['id'];
                        $movie['movie']['title'] = html_entity_decode($cur['title'], ENT_QUOTES, "UTF-8");
                        $movie['movie']['description'] = html_entity_decode($cur['description'], ENT_QUOTES, "UTF-8");
                        $movie['movie']['year'] = $curYear;
                        return true;
                    }
                }
        return false;
    }

    function extractTranslate($str, &$movie){
    	$result = array();
    	$res = preg_match_all('/\| *[\W](лицензия|чистый звук|звук с ts|l1|p|p2|Звук с CAMRip|iTunes|D|BaibaKo|L2)[\W]/isuU', $str.' ', $result);
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
    	
    	$res = preg_match_all('/[\W](dvdrip|dvdscr|hdrip|ts|tc|cam|brrip|webrip|bdrip|camrip|hdts|hdcam|hdtv|hdtvrip|telecine|web-dl|web-dlrip)[\W]/isuU', $str.' ', $result, PREG_OFFSET_CAPTURE);
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
        $imdbid=mysqli_real_escape_string($movie['imdbid']);
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE imdbid='$imdbid' AND updated > date_add(current_timestamp, interval -1 day)");
        if (!mysqli_num_rows($sqlresult))
            return false;
        $row = mysqli_fetch_assoc($sqlresult);
        $json = json_decode($row['description'], true);
        if (!$json or $json['Response'] == "False")
            return false;
        $img = "img/posters/$imdbid.jpg";
        $realImg = dirname( __FILE__ ) . "/../$img";
        if (array_key_exists("Poster", $json) && (!file_exists($realImg) or !filesize($realImg)) )
            return false;
        return true;
    }
    
    function addMovie($movie) {
        if (!$movie)
            return false;
        if (trySkipMovie($movie))
            return true;
        $imdbid=mysqli_real_escape_string($movie['imdbid']);
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE imdbid='$imdbid'");
        echo mysqli_error();
        if (!mysqli_num_rows($sqlresult)) {
            mysqli_query($GLOBALS['mysqli'], $mysqli, "INSERT INTO movies(imdbid) VALUES('$imdbid')");
            echo mysqli_error();
        }
        $title = mysqli_real_escape_string($movie['title']);

        $movie['description'] = file_get_contents("http://www.omdbapi.com/?i=" . urlencode($movie['imdbid']));           
        $json = json_decode($movie['description'], true);
        if (!$json or $json['Response'] == "False" )
            return false;

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
        $movie['description'] = json_encode($json);
        $description = mysqli_real_escape_string($movie['description']);

        //$year = (int)$movie['year'];
        mysqli_query($GLOBALS['mysqli'], "UPDATE movies SET title='$title', description='$description',updated=now() WHERE imdbid='$imdbid'");
        echo mysqli_error();
        return true;
    }
    
    function trySkip($cur) {
        static $cache = false;
        if (!$cache) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT md5 FROM links WHERE updated > date_add(current_timestamp, interval -1 day)");
            while ($row = mysqli_fetch_assoc($sqlresult))
                $cache[$row['md5']] = true;
        }
        return array_key_exists(md5($cur['link']), $cache);
        
        $hash = md5($cur['link']);
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE md5 = '$hash' AND updated > date_add(current_timestamp, interval -1 day)");
        return mysqli_num_rows($sqlresult);
    }
    
    function addLink($cur) {
        if (!addMovie($cur['movie']))
            return false;
        $hash = md5($cur['link']);
        $link = mysqli_real_escape_string($cur['link']);
        $imdbid = mysqli_real_escape_string($cur['movie']['imdbid']);

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE md5 = '$hash'");
        echo mysqli_error();
        if (!mysqli_num_rows($sqlresult)) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "INSERT INTO links(link,md5) VALUES('$link', '$hash')");
            echo mysqli_error();
        }
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id FROM movies WHERE imdbid='$imdbid'");
        echo mysqli_error();
        $id = mysqli_fetch_assoc($sqlresult);$id = (int)$id['id'];
        $description = mysqli_real_escape_string($cur['description']);
        $quality = mysqli_real_escape_string($cur['quality']);
        $translateQuality = array_key_exists('translateQuality',$cur)?$cur['translateQuality']:"";
        $size = (float)$cur['size'];
        $seed = (int)$cur['seed'];
        $leech = (int)$cur['leech'];
        mysqli_query($GLOBALS['mysqli'], "UPDATE links SET movieId=$id, description='$description', quality='$quality', translateQuality='$translateQuality', size=$size, seed=$seed, leech=$leech, updated=now() WHERE md5 = '$hash'");
        echo mysqli_error();
    }
?>