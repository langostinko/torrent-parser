<?php
    include_once(__DIR__."/pass.php");
    include_once(__DIR__."/vkStuff.php");
    include_once(__DIR__."/core.php");

    function setSettings(&$user, $settings) {
        $userId = $user['id'];
        $quality = $user['quality'] = $settings['quality'];
        $minRating = $user['minRating'] = $settings['minRating'];
        $maxDaysDif = $user['maxDaysDif'] = $settings['maxDaysDif'];
        $onlyNewTor = $user['onlyNewTor'] = $settings['onlyNewTor'];
        $minVotes = $user['minVotes'] = $settings['minVotes'];
        $translateQuality = $user['translateQuality'] = $settings['translateQuality'];
        if ($userId != 3)
            mysqli_query($GLOBALS['mysqli'], "UPDATE users SET quality=$quality, minRating=$minRating, maxDaysDif=$maxDaysDif, onlyNewTor=$onlyNewTor, minVotes=$minVotes, translateQuality=$translateQuality WHERE id=$userId");
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
        $_SESSION['expires'] = time() + 30*24*60*60;
        $_SESSION['showSettings'] = true;
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