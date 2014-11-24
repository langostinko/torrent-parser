<?php
include_once "lib/lib.php";
include_once "lib/libPirate.php";
include_once "lib/libRutor.php";
include_once "lib/libSeedoff.php";

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

function updateLinks(){
    echo "UPDATE LINKS\n";
    $resPirate = array();
    $resRutor = array();

    $resRutor = rutor\getRutor();
    $resRutor = array_merge($resRutor, rutor\getRutor("http://alt.rutor.org/browse/1/1/0/2/"));
    flush();
    
    $resSeedoff = array();
    /*$resSeedoff = seedoff\getSeedoff();
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=2"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=3"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=4"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=5"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=14&options=0&order=5&by=2&pages=6"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=1"));
    $resSeedoff = array_merge($resSeedoff, seedoff\getSeedoff("http://www.seedoff.net/index.php?page=ajax&active=0&options=0&recommend=0&sticky=0&period=0&category=64&options=0&order=5&by=2&pages=2"));
    flush();*/

    $resPirate = array_merge($resPirate, pirate\getPirateBay("http://thepiratebay.se/top/207", 100));
    $resPirate = array_merge($resPirate, pirate\getPirateBay("http://thepiratebay.se/top/201", 50));
    flush();
    /*for ($page = 1; $page >= 0; --$page)
        $resPirate = array_merge($resPirate, pirate\getPirateBay("http://pirateproxy.in/browse/201/$page/7"));
    for ($page = 2; $page >= 0; --$page)
        $resPirate = array_merge($resPirate, pirate\getPirateBay("http://pirateproxy.in/browse/207/$page/7"));*/

    $resPirate = array_merge($resPirate, $resRutor, $resSeedoff);
    
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
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT imdbid,title FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (!trySkipMovie($row)) {
            echo "\t" . $row['title'] . "\n";
            addMovie($row);
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