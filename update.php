<?php
include_once __DIR__."/lib/lib.php";
include_once __DIR__."/lib/loaders/RutorLoader.php";
include_once __DIR__."/lib/loaders/NNMLoader.php";
include_once __DIR__."/lib/loaders/PirateLoader.php";
include_once __DIR__."/lib/loaders/libSeedoff.php";
require_once __DIR__."/lib/RollingCurl.php";

function deleteBanned() {
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT MIN(seed) FROM links WHERE link LIKE '%rutor.org%' AND updated > date_add(current_timestamp, interval -2 hour)");
    $row = mysqli_fetch_assoc($sqlresult);
    $minSeed = (int)$row['MIN(seed)'] * 2;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT link FROM links WHERE link LIKE '%rutor.org%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
    echo mysqli_num_rows($sqlresult) . " old banned Rutor deleted\n";
    while ($row = mysqli_fetch_assoc($sqlresult))
        echo "\t" . $row['link'] . "\n";
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE link LIKE '%rutor.org%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT MIN(seed) FROM links WHERE link LIKE '%seedoff.net%' AND updated > date_add(current_timestamp, interval -2 hour)");
    $row = mysqli_fetch_assoc($sqlresult);
    $minSeed = (int)$row['MIN(seed)'] * 2;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT link FROM links WHERE link LIKE '%seedoff.net%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
    echo mysqli_num_rows($sqlresult) . " old banned Seedoff deleted\n";
    while ($row = mysqli_fetch_assoc($sqlresult))
        echo "\t" . $row['link'] . "\n";
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE link LIKE '%seedoff.net%' AND updated < date_add(current_timestamp, interval -2 hour) AND seed > $minSeed");
}

function deleteOld(){
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE updated < date_add(current_timestamp, interval -".DELETELINKSAFTERDAYS." day)");
    echo mysqli_num_rows($sqlresult) . " old links deleted\n";
    while ($row = mysqli_fetch_assoc($sqlresult))
        echo "\t".$row['link']."\n";
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
            echo "\t".$row['title'];
            echo unlink($realImg) ? " :: poster deleted\n" : " :: poster not found\n";
            mysqli_query($GLOBALS['mysqli'], "DELETE FROM movies WHERE id = " . $row['id']);
        }
}

function main_callback($response, $info, $request) {
    call_user_func($request->cookie['callback'], $response, $info, $request);
}

function updateLinks(){
    echo "UPDATE LINKS\n";
    $resPirate = array();
    
    //parallel RollingCurl
    
    RollingCurl::$rc = new RollingCurl("main_callback");
    // the window size determines how many simultaneous requests to allow.  
    RollingCurl::$rc->window_size = 10;

    //List of tracker loaders
    $loaders = array();
    $rutorMain = RUTORROOT;
    $loaders[] = new RutorLoader("$rutorMain/browse/0/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/1/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/2/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/3/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/4/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/5/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/6/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/7/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/8/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/9/1/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/0/7/0/2/");
    $loaders[] = new RutorLoader("$rutorMain/browse/0/5/0/2");
    
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
        "f[1]" => 218,
        "o" => 10,
        "s" => 2,
        "tm" => -1,
        "ta" => -1,
        "sns" => -1,
        "sds" => -1,
        "nm" => "",
        "pn" => "");
    $loaders[] = new NNMLoader("http://nnm-club.me/forum/tracker.php", $NNMData);

    $pirateMain = PIRATEROOT;
    $loaders[] = new PirateLoader("$pirateMain/browse/201/0/7/0");
    /*
    $resPirate1 = new Pirate;
    $resPirate1->getPirateBay("https://pirateproxy.sx/browse/207/0/7");
    $resPirate2 = new Pirate;
    $resPirate2->getPirateBay("https://pirateproxy.sx/browse/207/1/7");
    $resPirate3 = new Pirate;
    $resPirate3->getPirateBay("https://pirateproxy.sx/browse/201/0/7");
    $resPirate = array_merge($resPirate1->result, $resPirate2->result, $resPirate3->result, $resRutor, $resSeedoff);
    flush();
    */

    foreach ($loaders as $loader)
        $loader->load();

    RollingCurl::$rc->execute();
    
    //result array with torrent infos
    $result = array();
    foreach ($loaders as $loader) {
        $result = array_merge($result, $loader->getResult());
    }

    $resSeedoff = array();
    $resSeedoff = seedoff\getSeedoff();
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=2"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=3"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=1"));
    $result = array_merge($result, $resSeedoff);

    foreach($result as $cur) {
        if (trySkip($cur))
            continue;
    
        getIds($cur['title_approx'], $cur);
        echo "\t".$cur['description'] . "\t" . $cur['link'] . "\n";
    
        addLink($cur);
        usleep(100*1000);
    }
    echo count($result) . " links updated\n";
}

function updateMovies(){
    echo "UPDATE MOVIES\n";
    $sqlresult = mysqli_query($GLOBALS['mysqli'], 
    "   UPDATE movies c
        INNER JOIN (
          SELECT movieId, SUM(seed)+SUM(leech) as total
          FROM links
          WHERE `translateQuality` != \"ORIGINAL\"
          GROUP BY movieId
        ) x ON c.id = x.movieId
        SET c.max_peers = GREATEST(c.max_peers, x.total)    
    "
    );
    echo mysqli_error($GLOBALS['mysqli']);
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM `movies` WHERE `movies`.id in (SELECT movieId FROM links)");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!trySkipMovie($row)) {
            addMovie($row);
            echo "\t" . $row['title'] . "\n";
            print_r($row);
        }
}

header('Content-Type: text/plain; charset=UTF-8');
connect();
set_time_limit(5*60);

$time_start = microtime(true);

updateLinks();
echo "\n";
deleteBanned();
echo "\n";
deleteOld();
echo "\n";
updateMovies();
echo "\n";

$time_end = microtime(true);
$time = $time_end - $time_start;
echo "in $time seconds\n";


?>