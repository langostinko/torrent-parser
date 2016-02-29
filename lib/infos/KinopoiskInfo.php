<?php
include_once(__DIR__."/AbstractInfo.php");
include_once(__DIR__.'/../lib.php');
include_once(__DIR__.'/../simple_html_dom.php');

class KinopoiskInfo extends AbstractInfo {
    
    static private function getLink($link) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'kinopoisk_cookies.jar');
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'kinopoisk_cookies.jar');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate,sdch');
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.kinopoisk.ru/');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.122 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    static public function suggest($sugTitle, $sugYear, &$result) {
        $link = "http://www.kinopoisk.ru/search/films/?text=".urlencode($sugTitle);
        $response = KinopoiskInfo::getLink($link);
        
        $html = str_get_html($response);
        if (!$html)
            return "could not str_get_html from Kinopoisk suggest";
            
        foreach($html->find('div[class=film-snippet_type_movie]') as $row) {
            $name = $row->find('meta[itemprop=name]', 0);
            if (!$name)
                continue;
            $name = $name->getAttribute('content');

            $year = $row->find('div[class=film-snippet__info]', 0);
            if (!$year)
                continue;
            $year = substr($year->plaintext, -4);
            
            $id = $row->find('a[class=film-snippet__title]', 0);
            if (!$id)
                continue;
            $link = $id->href;
            $res = preg_match_all('/\/film\/(\d+)\//isu', $link, $id);
            if ($id && count($id[0]))
                $id = $id[1][0];
            else 
                $id = false;
                
            if ($name && $year && $id) {
                $needYear = $sugYear ? $sugYear : $year;
                if (abs($year - $needYear) <= 2) {
                    $result['id'] = $id;
                    $result['title'] = $name;
                    $result['year'] = $year;
                    return 0;
                }
            }
        }
        return "could not suggest from Kinopoisk";
    }

    static public function load($id, &$result) {
        $id = (int)$id;
        if (!$id)
            return "could not load Kinopoisk: kpid is not specified";
        $response = KinopoiskInfo::getLink("http://www.kinopoisk.ru/film/$id/details/");

        $html = str_get_html($response);
        if (!$html)
            return "could not str_get_html from Kinopoisk load";
        $table = $html->find('table[class=film-info_general]',0);
        if (!$table)
            return "could not find table from Kinopoisk load";

        foreach($table->find("tr") as $row) {
            $type = $row->find('td[class=film-info__type]', 0)->plaintext;
            $type = mb_strtolower($type, 'UTF-8');
            $value = $row->find('td[class=film-info__value]', 0)->plaintext;
            $value = html_entity_decode($value);
            $result[$type] = $value;
        }

        $result['plot'] = $html->find('div[class=kinoisland_movie-description]',0)->plaintext;
        $result['rating'] = (float)$html->find('div[class=film-header__rating]',0)->plaintext;

        $released = false;
        if (array_key_exists('премьера в мире', $result))
            $released = substr($result['премьера в мире'], 0, strpos($result['премьера в мире'], ','));
        if (!$released && array_key_exists('премьера в россии', $result))
            $released = substr($result['премьера в россии'], 0, strpos($result['премьера в россии'], ','));
        if ($released) {
    		$released = str_replace(
    		    array("января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря"),
    		    array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),
    		    $released);
            $result['release'] = strtotime($released);
        }

        $released = false;
        if (array_key_exists('премьера на dvd', $result))
            $released = substr($result['премьера на dvd'], 0, strpos($result['премьера на dvd'], ','));
        if ($released) {
    		$released = str_replace(
    		    array("января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря"),
    		    array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),
    		    $released);
            $result['releaseDVD'] = strtotime($released);
        }
        
        //TODO actors
        
        $img = "img/posters/{$id}Ru.jpg";
        $realImg = dirname( __FILE__ ) . "/../../$img";
        $url = "http:" . $html->find('img[class=film-meta__image]',0)->src . "_2";
        if ( !(file_exists($realImg) && filesize($realImg) > 4000) )
            file_put_contents($realImg, file_get_contents_curl($url));
        if (file_exists($realImg) &&    filesize($realImg) > 4000)
            $result['poster'] = $img;            

        return 0;
    }
    
}
?>