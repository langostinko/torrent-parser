<?php
require_once __DIR__."/lib/lib.php";
include_once __DIR__."/lib/loaders/RutorLoader.php";
include_once __DIR__."/lib/loaders/NNMLoader.php";
include_once __DIR__."/lib/loaders/PirateLoader.php";
include_once __DIR__."/lib/loaders/IviLoader.php";
include_once __DIR__."/lib/loaders/MegogoLoader.php";
include_once __DIR__."/lib/loaders/GooglePlayLoader.php";
include_once __DIR__."/lib/loaders/ITunesLoader.php";
include_once __DIR__."/lib/loaders/libSeedoff.php";
require_once __DIR__."/lib/RollingCurl.php";

function deleteBanned() {
    global $logger;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT MIN(seed) FROM links WHERE link LIKE '%rutor.org%' AND updated > date_add(current_timestamp, interval -2 hour)");
    $row = mysqli_fetch_assoc($sqlresult);
    $minSeed = (int)$row['MIN(seed)'] * 2;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT link FROM links WHERE link LIKE '%rutor.org%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
    $logger->info(mysqli_num_rows($sqlresult) . " old banned Rutor deleted");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $logger->info("\t" . $row['link']);
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE link LIKE '%rutor.org%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT MIN(seed) FROM links WHERE link LIKE '%seedoff.net%' AND updated > date_add(current_timestamp, interval -2 hour)");
    $row = mysqli_fetch_assoc($sqlresult);
    $minSeed = (int)$row['MIN(seed)'] * 2;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT link FROM links WHERE link LIKE '%seedoff.net%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
    $logger->info(mysqli_num_rows($sqlresult) . " old banned Seedoff deleted");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $logger->info("\t" . $row['link']);
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE link LIKE '%seedoff.net%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
}

function deleteOld(){
    global $logger;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE updated < date_add(current_timestamp, interval -".DELETELINKSAFTERDAYS." day)");
    $logger->info(mysqli_num_rows($sqlresult) . " old links deleted");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $logger->info("\t" . $row['link']);
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE updated < date_add(current_timestamp, interval -".DELETELINKSAFTERDAYS." day)");
    return;
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieid FROM links");
    $actual = array();
    while ($row = mysqli_fetch_assoc($sqlresult))
        $actual[$row['movieid']] = 1;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!array_key_exists($row['id'], $actual)) {
            $img = "img/posters/".$row['imdbid'].".jpg";
            $realImg = dirname( __FILE__ ) . "/$img";
            $logger->info("\t" . $row['title']);
            $logger->info(unlink($realImg) ? " :: poster deleted" : " :: poster not found");
            mysqli_query($GLOBALS['mysqli'], "DELETE FROM movies WHERE id = " . $row['id']);
        }
}

function main_callback($response, $info, $request) {
    call_user_func($request->cookie['callback'], $response, $info, $request);
}

function updateLinks(){
    global $logger;
    $logger->info('UPDATE LINKS');    
    $resPirate = array();
    
    //parallel RollingCurl
    
    RollingCurl::$rc = new RollingCurl("main_callback");
    // the window size determines how many simultaneous requests to allow.  
    RollingCurl::$rc->window_size = 3;

    //List of tracker loaders
    $loaders = array();
    $rutorMain = RUTORROOT;
    $loaders[] = new RutorLoader("$rutorMain/browse/0/1/0/2/");//foreign movies
    $loaders[] = new RutorLoader("$rutorMain/browse/1/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/2/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/3/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/4/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/5/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/6/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/7/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/8/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/9/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/10/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/11/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/12/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/13/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/0/7/0/2/");//multiplication
    $loaders[] = new RutorLoader("$rutorMain/browse/0/5/0/2");//russian movies
    $loaders[] = new RutorLoader("$rutorMain/browse/1/5/0/2");
    
    $NNMData = array(
        "prev_sd" => 0,
        "prev_a" => 0,
        "prev_my" => 0,
        "prev_n" => 0,
        "prev_shc" => 0,
        "prev_shf" => 1,
        "prev_sha" => 1,
        "prev_shs" => 0,
        "prev_shr" => 0,
        "prev_sht" => 0,
        "f[0]" => 270,
        "o" => 10,
        "s" => 2,
        "tm" => -1,
        "ta" => -1,
        "sns" => -1,
        "sds" => -1,
        "nm" => "",
        "pn" => "");
    $loaders[] = new NNMLoader(NNMROOT . "/forum/tracker.php", $NNMData);
    $NNMData["f[0]"] = 218;
    $loaders[] = new NNMLoader(NNMROOT . "/forum/tracker.php", $NNMData);
    $NNMData["f[0]"] = 217;
    $NNMData["f[1]"] = 954;
    $loaders[] = new NNMLoader(NNMROOT . "/forum/tracker.php", $NNMData);

    /*$pirateMain = PIRATEROOT;
    $loaders[] = new PirateLoader("$pirateMain/browse/201/0/7");
    $loaders[] = new PirateLoader("$pirateMain/browse/207/0/7");
    $loaders[] = new PirateLoader("$pirateMain/browse/207/1/7");*/

    $loaders[] = new IviLoader(1674, 0, 99); //movies-in-2015
    $loaders[] = new IviLoader(1674, 100, 199); //movies-in-2015
    $loaders[] = new IviLoader(1674, 200, 299); //movies-in-2015
    $loaders[] = new IviLoader(1982); //movie-new
    $loaders[] = new IviLoader(1983); //animation-new
    
    $loaders[] = new MegogoLoader("http://megogo.net/ru/premiere/page_1?ajax=true");
    $loaders[] = new MegogoLoader("http://megogo.net/ru/premiere/page_2?ajax=true");
    $loaders[] = new MegogoLoader("http://megogo.net/ru/premiere/page_3?ajax=true");
    
    $loaders[] = new GooglePlayLoader("https://play.google.com/store/movies/top?hl=ru");
    $loaders[] = new GooglePlayLoader("https://play.google.com/store/movies/category/1/collection/movers_shakers?hl=ru");
    $loaders[] = new GooglePlayLoader("https://play.google.com/store/movies/category/4/collection/movers_shakers?hl=ru");
    $loaders[] = new GooglePlayLoader("https://play.google.com/store/movies/category/6/collection/movers_shakers?hl=ru");
    $loaders[] = new GooglePlayLoader("https://play.google.com/store/movies/category/10/collection/movers_shakers?hl=ru");
    
    $loaders[] = new ITunesLoader("http://www.apple.com/ru/itunes/charts/movies/");

    foreach ($loaders as $loader) {
        $loader->setLogger($logger);
        $loader->load();
    }

    RollingCurl::$rc->execute();
    
    //result array with torrent infos
    $result = array();
    foreach ($loaders as $loader) {
        $result = array_merge($result, $loader->getResult());
    }

    //$resSeedoff = array();
    //$resSeedoff = seedoff\getSeedoff();
    //$resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=2"));
    //$resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=3"));
    //$resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=1"));
    //$result = array_merge($result, $resSeedoff);

    foreach($result as $cur) {
        if (trySkip($cur))
            continue;
        usleep(100*1000);
    
        getIds($cur['title_approx'], $cur);
        if (!$cur['movie']) {
            $logger->warning("could not find ids for '{$cur['title_approx']} ({$cur['year']}) : {$cur['link']}'");
            continue;
        }
        
        $logger->info("add link: " . $cur['title_approx'] . "::" . $cur['description'] . "::" . $cur['link']);
        $res = addLink($cur);
        if ($res !== 0)
            $logger->warning("link was not added: $res");
    }
    $logger->info(count($result) . " links updated");
}

function updateMovies(){
    global $logger;
    $logger->info("UPDATE MOVIES");
    $sqlresult = mysqli_query($GLOBALS['mysqli'], 
    "   UPDATE movies c
        INNER JOIN (
          SELECT movieId, SUM(seed)+SUM(leech) as total
          FROM links
          WHERE `translateQuality` != \"ORIGINAL\"
          GROUP BY movieId
        ) x ON c.id = x.movieId
        SET c.sum_peers = x.total
    "
    );
    if (mysqli_errno($GLOBALS['mysqli']))
        $logger->error(mysqli_error($GLOBALS['mysqli']));
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM `movies` WHERE `movies`.id in (SELECT movieId FROM links)");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if ($reason = trySkipMovie($row)) {
            $logger->info($row['title'] . ": $reason");
            $res = addMovie($row);
            if ($res !== 0)
                $logger->warning($res);
            //$logger->info(print_r($row, true));
        }
}

function pushMovies(){
    function curlCallback($response, $info, $request) {
        global $logger;
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $logger->warning($msg);
            return;
        }
        $logger->info($msg);
        mysqli_query($GLOBALS['mysqli'], "INSERT INTO pushed_movies (movieId) VALUES (".$request->cookie.")");
    }
    global $logger;
    $logger->info("PUSH MOVIES");
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id,movieId,quality,seed,leech from links");
    $movies = array();
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (qualityToRool($row['quality'])) {
            $movies[$row['movieId']] = @$movies[$row['movieId']] + $row['seed'] + $row['leech'];
        }
    }

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * from pushed_movies");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        unset($movies[$row['movieId']]);
    }

    foreach ($movies as $id => $peers) {
        if ($peers > 2000) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT title, description from movies where id = $id");
            $row = mysqli_fetch_assoc($sqlresult);
            $title = $row['title'];
            $desc = json_decode($row['description'], true);
            $rating = array_key_exists('kinopoiskRating', $desc) ? $desc['kinopoiskRating'] : 0;
            if (!$rating) {
                $rating = array_key_exists('imdbRating', $desc) ? $desc['imdbRating'] : 0;
            }
            if ((float)$rating < 6 && $peers < 4000) {
                continue;
            }
            $message = "$title\nhttp://freshswag.ru/movie.php?id=$id";
            //$link = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendMessage?chat_id=329766242&text=".urlencode($message);
            $link = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendMessage?chat_id=@freshswag&text=".urlencode($message);
            $rc = new RollingCurl("curlCallback");
            $rc->get($link, null, null, $id );
            $rc->execute();
            $logger->info("PUSH to telegram: " . $link);
            break;
        }
    }
}

header('Content-Type: text/plain; charset=UTF-8');
connect();
set_time_limit(5*60);

$logger->info("update script started");

$time_start = microtime(true);

updateLinks();
deleteBanned();
deleteOld();
pushMovies();
//updateMovies();

$time_end = microtime(true);
$time = $time_end - $time_start;

$logger->info("update script finished in $time seconds");
?>