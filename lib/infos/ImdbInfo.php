<?php
include_once(__DIR__."/AbstractInfo.php");

class ImdbInfo extends AbstractInfo {

    static public function suggest($sugTitle, $sugYear, &$result) {
        $sugTitle = translit_utf8($sugTitle);
        $link = "http://www.imdb.com/xml/find?json=1&nr=1&tt=on&q=" . urlencode($sugTitle);

        $file = file_get_contents_curl($link);
        $json = json_decode($file, true);

        $vector = array('title_popular','title_exact','title_approx');
        $curDif = 2;
        foreach($vector as $type)
            if ($json and array_key_exists($type, $json))
                foreach ($json[$type] as $cur) {
                    $year = (int)substr($cur['description'],0,4);
                    $needYear = $sugYear ? $sugYear : $year;
                    if (abs($year - $needYear) < $curDif) {
                        $result['id'] = $cur['id'];
                        $result['title'] = html_entity_decode($cur['title'], ENT_QUOTES, "UTF-8");
                        $result['year'] = $year;
                        $curDif = abs($year - $needYear);
                    }
                }
        return ($curDif < 2)?0:"could not suggest from IMDB";
    }

    static public function load($id, &$result) {
        if (!$id)
            return "could not load IMDB: imdbid is not specified";
        $omdbapi = file_get_contents_curl("http://www.omdbapi.com/?i=" . urlencode($id));           
        $json = json_decode($omdbapi, true);
        if (!$json)
            return "could not get imdb description";
        foreach($json as $key => $value) {
            $key = mb_strtolower($key, 'UTF-8');
            if ($key == "poster") {
                if ($key != "N/A") {
                    $img = "img/posters/$id.jpg";
                    $realImg = dirname( __FILE__ ) . "/../../$img";
                    if ( !(file_exists($realImg) && filesize($realImg)) )
                        file_put_contents($realImg, file_get_contents_curl($value));
                    if (file_exists($realImg) && filesize($realImg))
                        $result[$key] = $img;
                }
            } else
                $result[$key] = $value;
        }
        return 0;
    }
    
}
?>