<?php
require_once __DIR__."/lib/lib.php";
include_once __DIR__."/lib/loaders/FilmTorrentLoader.php";
include_once __DIR__."/lib/loaders/RutorLoader.php";
include_once __DIR__."/lib/loaders/NNMLoader.php";
include_once __DIR__."/lib/loaders/PirateLoader.php";
include_once __DIR__."/lib/loaders/IviLoader.php";
include_once __DIR__."/lib/loaders/MegogoLoader.php";
include_once __DIR__."/lib/loaders/GooglePlayLoader.php";
include_once __DIR__."/lib/loaders/ITunesLoader.php";
require_once __DIR__."/lib/RollingCurl.php";

function deleteOld(){
    global $logger;
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE updated < date_add(current_timestamp, interval -".DELETELINKSAFTERDAYS." day)");
    $logger->info(mysqli_num_rows($sqlresult) . " old links deleted");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $logger->info("\t" . $row['link']);
    mysqli_query($GLOBALS['mysqli'], "DELETE FROM links WHERE updated < date_add(current_timestamp, interval -".DELETELINKSAFTERDAYS." day)");
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
    $rutorSuffix = ";" . date("d.m.Y", time() - 6 * 30 * 24 * 3600);
    $loaders[] = new RutorLoader("$rutorMain/browse/0/1/0/2$rutorSuffix");//foreign movies
    $loaders[] = new RutorLoader("$rutorMain/browse/1/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/2/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/3/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/4/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/5/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/6/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/7/1/0/2$rutorSuffix");
    $loaders[] = new RutorLoader("$rutorMain/browse/0/6/0/2$rutorSuffix");//multiplication
    $loaders[] = new RutorLoader("$rutorMain/browse/0/7/0/2$rutorSuffix");//anime
    $loaders[] = new RutorLoader("$rutorMain/browse/0/2/0/2$rutorSuffix");//russian movies
    $loaders[] = new RutorLoader("$rutorMain/browse/1/2/0/2$rutorSuffix");

    $loaders[] = new FilmTorrentLoader("http://filmitorrent.org");
    $loaders[] = new FilmTorrentLoader("http://filmitorrent.org/page/2/");
    $loaders[] = new FilmTorrentLoader("http://filmitorrent.org/page/3/");

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
}

function pushMovies(){
    function pushCurlCallback($response, $info, $request) {
        global $logger;
        $msg = $info['http_code'] . " :: " . $info['url'] . " fetched in " . $info['total_time'];
        if ($info['http_code'] != 200) {
            $logger->warning($msg);
            return;
        }
        $logger->info($msg);
        if ($request->cookie && array_key_exists('id', $request->cookie)) {
            mysqli_query($GLOBALS['mysqli'],
                "INSERT INTO pushed_movies (movieId, translateQuality) VALUES ("
                .$request->cookie["id"]
                .", '".mysqli_real_escape_string($GLOBALS['mysqli'], $request->cookie["translateQuality"])
                ."')"
            );
        }
    }
    global $logger;
    $logger->info("PUSH MOVIES");
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT id,movieId,quality,seed,leech from links");
    $movies = array();
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (qualityToRool($row['quality'])) {
            $movies[$row['movieId']] = array(
                "peers" => @$movies[$row['movieId']]['peers'] + $row['seed'] + $row['leech']
                , "pushedTranslateQuality" => "ORIGINAL"
            );
        }
    }

    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * from pushed_movies");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        if (array_key_exists($row['movieId'], $movies)) {
            if (translateQualityToRool($movies[$row['movieId']]['pushedTranslateQuality']) < translateQualityToRool($row['translateQuality'])) {
                $movies[$row['movieId']]['pushedTranslateQuality'] = $row['translateQuality'];
            }
        }
    }

    foreach ($movies as $id => $stat) {
        $peerThreshold = 1000;
        if ($stat['peers'] > 1000) {
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT title, description from movies where id = $id");
            $row = mysqli_fetch_assoc($sqlresult);
            $title = $row['title'];
            $desc = json_decode($row['description'], true);
            $rating = array_key_exists('kinopoiskRating', $desc) ? $desc['kinopoiskRating'] : 0;
            if (!$rating) {
                $rating = array_key_exists('imdbRating', $desc) ? $desc['imdbRating'] : 0;
            }
            if ((float)$rating < 7.0) {
                $peerThreshold = 2000;
            }
            if ((float)$rating < 6.0) {
                $peerThreshold = 4000;
            }
            if ($stat['peers'] < $peerThreshold) {
                continue;
            }

            // get best quality
            $bestQuality = array('quality'=>"CAMRIP", 'translateQuality'=>"ORIGINAL");
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $id AND type=0 ORDER BY seed DESC LIMIT 500");
            while ($row = mysqli_fetch_assoc($sqlresult)) {
                if (qualityToRool($row['quality']) > qualityToRool($bestQuality['quality'])
                || (qualityToRool($row['quality']) == qualityToRool($bestQuality['quality']) && translateQualityToRool($row['translateQuality']) > translateQualityToRool($bestQuality['translateQuality']) ) ) {
                    $bestQuality['quality'] = $row['quality'];
                    $bestQuality['translateQuality'] = $row['translateQuality'];
                }
                $torrents[] = $row;
            }
            $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $id AND type=1 LIMIT 500");
            while ($row = mysqli_fetch_assoc($sqlresult)) {
                if (qualityToRool($row['quality']) > qualityToRool($bestQuality['quality'])
                || (qualityToRool($row['quality']) == qualityToRool($bestQuality['quality']) && translateQualityToRool($row['translateQuality']) > translateQualityToRool($bestQuality['translateQuality']) ) ) {
                    $bestQuality['quality'] = $row['quality'];
                    $bestQuality['translateQuality'] = $row['translateQuality'];
                }
                $legals[] = $row;
            }
            $bestQuality['quality'] = mb_strtoupper($bestQuality['quality'], "UTF-8");
            $bestQuality['translateQuality'] = mb_strtoupper($bestQuality['translateQuality'], "UTF-8");
            if (qualityToRool($bestQuality['quality']) < qualityToRool("HD")) {
                continue;
            }
            if (translateQualityToRool($bestQuality['translateQuality']) <= translateQualityToRool($stat['pushedTranslateQuality'])) {
                continue;
            }
            // increase peer threshold for low quality
            if (translateQualityToRool($bestQuality['translateQuality']) < translateQualityToRool("ITUNES") && $stat['peers'] < $peerThreshold * 2) {
                continue;
            }

            $message = "<b>$title</b>";
            $message .= "\n" . $bestQuality['quality'] . (@$bestQuality['translateQuality'] ? (", перевод: " . $bestQuality['translateQuality']) : "");
            $message .= @$desc['kinopoiskRating'] ? ("\nКинопоиск: " . sprintf("%.1f", $desc['kinopoiskRating'])) : "";
            $message .= @$desc['imdbRating'] ? ("\nIMDB: " . sprintf("%.1f", $desc['imdbRating'])) : "";

            $genre = @$desc['жанр']??@$desc['Genre'];
            $genrePos = min(mb_strlen($genre), 20);
            $genre = mb_substr($genre, 0, mb_strpos($genre, ',', $genrePos) ? mb_strpos($genre, ',', $genrePos) : NULL );
            $message .= "\n" . $genre;

            $actors = @$desc['актеры']??@$desc['Actors'];
            $actorsPos = min(mb_strlen($actors), 30);
            $actors = mb_substr($actors, 0, mb_strpos($actors, ',', $actorsPos) ? mb_strpos($actors, ',', $actorsPos) : NULL);
            $message .= "\nактеры: " . $actors;

            $plot = @$desc['plotRu'] ? ("\n" . $desc['plotRu']) : "";
            $plotPos = min(mb_strlen($plot), 200);
            $plot = mb_substr($plot, 0, mb_strpos($plot, '.', $plotPos) ? mb_strpos($plot, '.', $plotPos) : NULL);
            $message .= "\n" . $plot;

            $message .= "\nhttp://freshswag.ru/movie.php?id=$id";
            preg_match_all('/\d+$/isu', @$desc['kinopoiskId'], $shortid);
            $shortid = @$shortid[0][0];
            $imgSrc = "http://st.kp.yandex.net/images/film_iphone/iphone360_$shortid.jpg";
            //$imgSrc = "http://freshswag.ru/" . (array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']);

            //$messageLink = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendMessage?chat_id=329766242&parse_mode=HTML&text=".urlencode($message);
            //$photoLink = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendPhoto?chat_id=329766242&photo=".urlencode($imgSrc);
            $messageLink = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendMessage?chat_id=@freshswag&parse_mode=HTML&text=".urlencode($message);
            $photoLink = "https://api.telegram.org/bot" . \pass\Telegram::$token . "/sendPhoto?chat_id=@freshswag&photo=".urlencode($imgSrc);
            $rc = new RollingCurl("pushCurlCallback");
            $rc->get($photoLink, null, null);
            $rc->execute();
            $rc = new RollingCurl("pushCurlCallback");
            $rc->get($messageLink, null, null, array("id" => $id, "translateQuality" => $bestQuality['translateQuality']) );
            $rc->execute();
            $logger->info("PUSH to telegram: " . $messageLink);
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
deleteOld();
pushMovies();
updateMovies();

$time_end = microtime(true);
$time = $time_end - $time_start;

$logger->info("update script finished in $time seconds");
?>