<?php

function cmpByLeech(&$a, &$b) {
    $a['sortVal'] = $a["totalLeech"];
    $b['sortVal'] = $b["totalLeech"];
    return $a["sortVal"] < $b["sortVal"];
}

function cmpByRatingLeech(&$a, &$b) {
    $a['sortVal'] = exp(-($a["totalLeech"]+$a["totalSeed"])/20000.0)*exp($a['kinopoiskRating']);
    $b['sortVal'] = exp(-($b["totalLeech"]+$b["totalSeed"])/20000.0)*exp($b['kinopoiskRating']);
    return $a["sortVal"] < $b["sortVal"];
}

function calcTotalSeedLeech(&$movies, $ignore, $user) {
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!array_key_exists($row['movieId'], $ignore)) {
            if ($user['onlyNewTor']) {
                $added = strtotime($row['added']);
                if ( (time() - $added)/(24*60*60) > 7)
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
            $movies[(int)$row['movieId']]['totalSeed'] += $row['seed'];
            $movies[(int)$row['movieId']]['totalLeech'] += $row['leech'];
            $movies[(int)$row['movieId']]['userTake'] = true;
        }
      
}

function sortBySeedLeech(&$movies, $ignore, $user) {
    calcTotalSeedLeech($movies, $ignore, $user);

    $take = array();
    foreach($movies as $key=>$movie) 
        if ($movie['userTake']) {
            $take[$key] = array(
                    "id"=>$key,
                    "totalSeed"=>$movie['totalSeed'],
                    "totalLeech"=>$movie['totalLeech'],
                    "imdbRating"=>(float)$movie['description']['imdbRating'],
                    "kinopoiskRating"=>(float)$movie['description']['kinopoiskRating'],
                    );
        }
    usort($take, "cmpByLeech");
    //usort($take, "cmpByRatingLeech");

    return $take;
}

?>