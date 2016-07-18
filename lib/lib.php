<?php
    include_once(__DIR__."/defines.php");
    include_once(__DIR__."/pass.php");
    include_once(__DIR__."/vkStuff.php");
    include_once(__DIR__."/core.php");
    
    //set session cookie lifetime
    ini_set('session.gc_maxlifetime', 60*60*24); //24h
    $lifetime=7*24*60*60;
    session_start();
    setcookie(session_name(),session_id(),time()+$lifetime);
    //
    
    function isAdmin($id) {
        return $id == 25;
    }

    function printTime() {
        $time = microtime(true) - $GLOBALS['head_time_start'];
        echo "<!--time::$time-->\n";
    }

    function getRandomList() {
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT title, description FROM `movies` WHERE `movies`.id in (SELECT movieId FROM links) ORDER BY sum_peers DESC LIMIT 40");
        $vars = array();
        $rows = array();
        while ($row = mysqli_fetch_assoc($sqlresult))
            if ($row['description'])
                $rows[] = json_decode($row['description'], true);
        foreach ($rows as $desc)
            if (array_key_exists('жанр', $desc)) {
                $genres = explode(", ", $desc['жанр']);
                foreach ($genres as $value) 
                    if (!in_array($value, $vars))
                        $vars[] = $value;
            }
        foreach ($rows as $desc)
            if (array_key_exists("titleRu", $desc))
                $vars[] = mb_strtolower($desc['titleRu'], "utf-8");
        foreach ($rows as $desc)
            if (array_key_exists('актеры', $desc)) {
                $actors = explode(", ", $desc['актеры']);
                $vars[] = mb_strtolower($actors[0], "utf-8");
            }
        return $vars;
    }
  
    function setSettings(&$user, $settings) {
        $userId = $user['id'];
        $quality = $user['quality'] = $settings['quality'];

        $minRating = $user['minRating'];
        if (array_key_exists('minRating', $settings))
            $minRating = $user['minRating'] = $settings['minRating'];

        $maxDaysDif = $user['maxDaysDif'];
        if (array_key_exists('maxDaysDif', $settings))
            $maxDaysDif = $user['maxDaysDif'] = $settings['maxDaysDif'];

        $minVotes = $user['minVotes'];
        if (array_key_exists('minVotes', $settings))
            $minVotes = $user['minVotes'] = $settings['minVotes'];

        $kpID = $user['kpID'];
        if (array_key_exists('kpID', $settings))
            $kpID = $user['kpID'] = $settings['kpID'];

        $onlyNewTor = $user['onlyNewTor'] = $settings['onlyNewTor'];
        $translateQuality = $user['translateQuality'] = $settings['translateQuality'];
        $onlyLegal = $user['onlyLegal'] = $settings['onlyLegal'];
        $maxCost = $user['maxCost'] = $settings['maxCost'];
        $sortType = $user['sortType'] = $settings['sortType'];
        if ($userId != 3)
            mysqli_query($GLOBALS['mysqli'], "UPDATE users SET quality=$quality, minRating=$minRating, maxDaysDif=$maxDaysDif, onlyNewTor=$onlyNewTor, minVotes=$minVotes, translateQuality=$translateQuality, onlyLegal=$onlyLegal, maxCost=$maxCost, sortType=$sortType, kpID=$kpID WHERE id=$userId");
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
        if (array_key_exists('new_movies', $_GET))
            $guestLogin = 'new_movies';
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM users WHERE login='$guestLogin'" );
        $_SESSION['user'] = mysqli_fetch_assoc($sqlresult);
        $_SESSION['expires'] = time() + 30*24*60*60;
        $_SESSION['showSettings'] = true;
        return false;
    }

    function connect(){
        global $mysqli;
    	$host = \pass\SQL::$host;
    	$user = \pass\SQL::$user;
    	$pwd = \pass\SQL::$pwd;
    	$base = \pass\SQL::$base;
    	$mysqli = mysqli_connect($host, $user, $pwd);
    	if (!$mysqli) 
    		die("Connection error: " . mysqli_error($GLOBALS['mysqli']));
    	// переменные для работы базы данных
    	mysqli_select_db ($mysqli, $base) or die ("Не могу соединиться с базой данных. Ошибка: " . mysql_error());
    	mysqli_set_charset($mysqli, 'utf8');
    }
?>