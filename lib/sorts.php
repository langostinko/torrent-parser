<?php
include_once(__DIR__."/defines.php");

function cmpByLeech(&$a, &$b) {
    $a['sortVal'] = $a["totalLeech"];
    $b['sortVal'] = $b["totalLeech"];
    return $a["sortVal"] < $b["sortVal"];
}

function cmpByOcc(&$a, &$b) {
    $a['sortVal'] = $a["firstOcc"];
    $b['sortVal'] = $b["firstOcc"];
    return $a["sortVal"] < $b["sortVal"];
}

function cmpBySeedLeech(&$a, &$b) {
    $a['sortVal'] = $a["totalLeech"] + $a["totalSeed"];
    $b['sortVal'] = $b["totalLeech"] + $b["totalSeed"];
    return $a["sortVal"] < $b["sortVal"];
}

function cmpByRatingLeech(&$a, &$b) {
    $a['sortVal'] = exp(-($a["totalLeech"]+$a["totalSeed"])/5000.0)*exp($a['kinopoiskRating']);
    $b['sortVal'] = exp(-($b["totalLeech"]+$b["totalSeed"])/5000.0)*exp($b['kinopoiskRating']);
    return $a["sortVal"] < $b["sortVal"];
}

function calcTotalSeedLeech(&$movies, $ignore, $user) {
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links ORDER BY seed DESC");
    $igList = array();
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!array_key_exists($row['movieId'], $ignore)) {
            if (in_array($row['movieId'], $igList))
                continue;
            
            if ($user['onlyNewTor']) {
                $added = strtotime($row['added_tracker']?$row['added_tracker']:$row['added']);
                if ( (time() - $added)/(24*60*60) > FRESHLINKSDAYS)
                    continue;
            }

            if (qualityToRool($row['quality']) < $user['quality'])
                continue;
            if (translateQualityToRool($row['translateQuality']) < $user['translateQuality'])
                continue;
            if (!is_array($movies[$row['movieId']]['description']))
                $movies[$row['movieId']]['description'] = json_decode($movies[$row['movieId']]['description'], true);
            if (array_key_exists("kinopoiskRating", $movies[$row['movieId']]['description'])) {
                if ((float)$movies[$row['movieId']]['description']['kinopoiskRating'] < $user['minRating'])
                    continue;
            } else if ((float)$movies[$row['movieId']]['description']['imdbRating'] < $user['minRating'])
                continue;
            if ($user['minVotes']) {
                $votes = intval(str_replace(",","",$movies[$row['movieId']]['description']['imdbVotes']));;
                if ($votes < $user['minVotes']) 
                    continue;
            }

            if (empty($movies[$row['movieId']]['Release']))
                $movies[$row['movieId']]['Release'] = strtotime($movies[$row['movieId']]['description']['Released']);

            if ($user['maxDaysDif']) {
                if ((time()-$movies[$row['movieId']]['Release'])/(30.417*24*60*60) > $user['maxDaysDif'])
                    continue;
            }
            if (!(array_key_exists("Poster", $movies[$row['movieId']]['description']) && $movies[$row['movieId']]['description']['Poster'] != 'N/A'))
                continue;
            $movies[(int)$row['movieId']]['userTake'] = true;
                
            @$movies[(int)$row['movieId']]['totalSeed'] += $row['seed'];
            @$movies[(int)$row['movieId']]['totalLeech'] += $row['leech'];
            if (!array_key_exists("firstOcc", $movies[(int)$row['movieId']]))
                $movies[(int)$row['movieId']]['firstOcc'] = strtotime($row['added_tracker']);
            $movies[(int)$row['movieId']]['firstOcc'] = min($movies[(int)$row['movieId']]['firstOcc'],strtotime($row['added_tracker']));
            
            if (qualityToRool($row['quality']) > @$movies[(int)$row['movieId']]['quality']) {
                $movies[(int)$row['movieId']]['quality'] = qualityToRool($row['quality']);
                $movies[(int)$row['movieId']]['qualityStr'] = $row['quality'];
                $movies[(int)$row['movieId']]['translateQuality'] = translateQualityToRool($row['translateQuality']);
                $movies[(int)$row['movieId']]['translateQualityStr'] = $row['translateQuality'];
            }
            if (qualityToRool($row['quality']) == $movies[(int)$row['movieId']]['quality'] &&
                translateQualityToRool($row['translateQuality']) > $movies[(int)$row['movieId']]['translateQuality'] ) {
                    $movies[(int)$row['movieId']]['quality'] = qualityToRool($row['quality']);
                    $movies[(int)$row['movieId']]['qualityStr'] = $row['quality'];
                    $movies[(int)$row['movieId']]['translateQuality'] = translateQualityToRool($row['translateQuality']);
                    $movies[(int)$row['movieId']]['translateQualityStr'] = $row['translateQuality'];
                }
        }
      
}

function sortBySeedLeech(&$movies, $ignore, $user) {
    calcTotalSeedLeech($movies, $ignore, $user);

    $take = array();
    foreach($movies as $key=>$movie) 
        if (@$movie['userTake']) {
            $take[$key] = array(
                    "id"=>$key,
                    "totalSeed"=>$movie['totalSeed'],
                    "totalLeech"=>$movie['totalLeech'],
                    "firstOcc"=>$movie['firstOcc'],
                    "imdbRating"=>(float)$movie['description']['imdbRating'],
                    "kinopoiskRating"=>(float)$movie['description']['kinopoiskRating'],
                    );
        }
    /*if ($user['onlyNewTor'])
        usort($take, "cmpBySeedLeech");
    else
        usort($take, "cmpByLeech");
    */
    if (array_key_exists("underrated", $_GET))
        usort($take, "cmpByRatingLeech");
    else if (array_key_exists("last", $_GET))
        usort($take, "cmpByOcc");
    else
        usort($take, "cmpBySeedLeech");

    return $take;
}

?>