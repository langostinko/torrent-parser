<?php
include_once __DIR__."/lib/lib.php";
include_once __DIR__."/lib/libPirate.php";
include_once __DIR__."/lib/libRutor.php";
include_once __DIR__."/lib/libSeedoff.php";
require_once __DIR__."/lib/RollingCurl.php";

function deleteOld(){
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE updated < date_add(current_timestamp, interval -7 day)");
    echo mysqli_num_rows($sqlresult) . " old links deleted\n";
    while ($row = mysqli_fetch_assoc($sqlresult))
        echo "\t".$row['link']."\n";
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE updated < date_add(current_timestamp, interval -7 day)");
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

    $resRutor1 = new Rutor;
    $resRutor1->getRutor();
    $resRutor2 = new Rutor;
    $resRutor2->getRutor("http://alt.rutor.org/browse/1/1/0/2/");
    $resRutor3 = new Rutor;
    $resRutor3->getRutor("http://alt.rutor.org/browse/2/1/0/2/");
    $resRutor4 = new Rutor;
    $resRutor4->getRutor("http://alt.rutor.org/browse/3/1/0/2/");
    $resRutor5 = new Rutor;
    $resRutor5->getRutor("http://alt.rutor.org/browse/4/1/0/2/");
    $resRutor6 = new Rutor;
    $resRutor6->getRutor("http://alt.rutor.org/browse/5/1/0/2/");
    $resRutor7 = new Rutor;
    $resRutor7->getRutor("http://alt.rutor.org/browse/6/1/0/2/");
    $resRutor8 = new Rutor;
    $resRutor8->getRutor("http://alt.rutor.org/browse/7/1/0/2/");
    $resRutor9 = new Rutor;
    //$resRutor9->getRutor("http://alt.rutor.org/browse/8/1/0/2/");
    $resRutor10 = new Rutor;
    //$resRutor10->getRutor("http://alt.rutor.org/browse/9/1/0/2/");
    $resRutor11 = new Rutor;
    $resRutor11->getRutor("http://alt.rutor.org/browse/0/7/0/2/");
    flush();
    
    $resSeedoff = array();
    $resSeedoff = seedoff\getSeedoff();
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=2"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=3"));
    /*$resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=4"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=5"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=6"));*/
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=1"));
    //$resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=2"));
    flush();

    $resPirate1 = new Pirate;
    $resPirate1->getPirateBay("https://pirateproxy.sx/top/207", 100);
    //$resPirate1->getPirateBay("http://thepiratebay.se/top/207", 100);
    $resPirate2 = new Pirate;
    $resPirate2->getPirateBay("https://pirateproxy.sx/top/201", 50);
    //$resPirate2->getPirateBay("http://thepiratebay.se/top/201", 50);
    flush();

    RollingCurl::$rc->execute();
    $resRutor = array_merge($resRutor1->result, $resRutor2->result, $resRutor3->result, $resRutor4->result, $resRutor5->result, 
                            $resRutor6->result, $resRutor7->result, $resRutor8->result, $resRutor9->result, $resRutor10->result,
                            $resRutor11->result);
    $resPirate = array_merge($resPirate1->result, $resPirate2->result, $resRutor, $resSeedoff);
    
    foreach($resPirate as $cur) {
        if (trySkip($cur))
            continue;
    
        searchIMDB($cur['title_approx'], $cur);
        echo "\t".$cur['description'] . "\t" . $cur['link'] . "\n";
    
        addLink($cur);
        usleep(100*1000);
    }
    echo count($resPirate) . " links updated\n";
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
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id,imdbid,title,max_peers FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        $checkLink = mysqli_query($GLOBALS['mysqli'], "SELECT id FROM links WHERE movieId = " . $row['id']);
        if (mysqli_num_rows($checkLink) && !trySkipMovie($row)) {
            addMovie($row);
            echo "\t" . $row['title'] . "\n";
            print_r($row);
        }
    }
}

header('Content-Type: text/plain; charset=UTF-8');
connect();
set_time_limit(5*60);

$time_start = microtime(true);

updateLinks();
echo "\n";
deleteOld();
echo "\n";
updateMovies();
echo "\n";

$time_end = microtime(true);
$time = $time_end - $time_start;
echo "in $time seconds\n";


?>