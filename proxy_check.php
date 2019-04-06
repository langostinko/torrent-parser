<?php
    require_once __DIR__."/lib/lib.php";

    function main_callback($response, $info, $request) {
        //echo "proxy cache";
        //print_r(ProxyFinder::getCache());
        //echo "request";
        //print_r($request);
        //echo "info";
        //print_r($info);
        //echo "response";
        //print_r($response);
    }

    $proxy_str = '202.166.205.78  58431   
  Nepal Kathmandu
1860 мс

HTTP    Высокая 1 минута
145.239.169.44  1080    
  France
680 мс

HTTP    Высокая 1 минута
54.37.6.196 3128    
  United Kingdom
380 мс

HTTPS   Высокая 1 минута
178.128.174.206 3128    
  United Kingdom London
260 мс

HTTP    Высокая 1 минута
91.221.109.138  3128    
  Russian Federation
120 мс

HTTPS   Высокая 1 минута
85.28.88.155    3128    
  Belgium Saint-Josse-ten-Noode
480 мс

HTTPS   Высокая 1 минута
54.251.38.138   8080    
  Singapore Singapore
1800 мс

HTTP    Высокая 1 минута
138.197.157.32  8080    
  Canada Toronto
980 мс

HTTP    Высокая 1 минута
109.70.201.2    53517   
  Italy Como
2660 мс

HTTP    Высокая 2 минуты
122.201.112.114 80  
  Australia Sydney
1380 мс

HTTP    Средняя 2 минуты
188.166.170.113 3128    
  United Kingdom London
4540 мс

HTTP    Высокая 2 минуты
89.39.108.202   47283   
  Poland
3000 мс

HTTP    Высокая 2 минуты
67.205.146.29   3128    
  United States "North Bergen"
520 мс

HTTP    Высокая 2 минуты
119.81.71.27    80  
  Singapore Singapore
760 мс

HTTP, HTTPS Высокая 2 минуты
37.59.35.174    1080    
  France
640 мс

HTTP    Высокая 2 минуты
159.8.114.37    80  
  France Clichy
240 мс

HTTP, HTTPS Высокая 2 минуты
45.33.31.25 80  
  United States Dallas
3640 мс

HTTP    Высокая 2 минуты
194.67.37.90    3128    
  Russian Federation Moscow
120 мс

HTTP    Высокая 2 минуты
198.211.103.89  80  
  United States "North Bergen"
520 мс

HTTP    Средняя 2 минуты
157.230.236.97  80  
  United States "New York"
520 мс

HTTP    Средняя 2 минуты
91.221.109.136  3128    
  Russian Federation
140 мс

HTTPS   Высокая 2 минуты
34.244.2.233    8123    
  Ireland Dublin
340 мс

HTTP    Высокая 2 минуты';

    preg_match_all('/(\d+\.\d+\.\d+\.\d+) +(\d+)/', $proxy_str, $matches);
    $proxy_list = array();
    for ($i = 0; $i < count($matches[1]); ++$i) {
        $proxy_list[] = $matches[1][$i] . ":" . $matches[2][$i];
    }

    $good_proxy_list = array();
    while (count($proxy_list)) {
        print_r($proxy_list);
        ProxyFinder::setProxyList($proxy_list);
        $link = "http://filmitorrent.net";
        RollingCurl::$rc = new RollingCurl("main_callback");
        RollingCurl::$rc->get($link, null, null, null );
        RollingCurl::$rc->execute();
        $good_proxy = ProxyFinder::getCache()['filmitorrent.net'];
        echo "good proxy: " . $good_proxy . " : " . count($proxy_list) . " proxies left\n";
        $good_proxy_list[] = $good_proxy;
        $offset = array_search($good_proxy, $proxy_list) + 1;
        $proxy_list = array_slice($proxy_list, $offset);
    }
    echo "good_proxy_list: " . join('","', $good_proxy_list);
?>
