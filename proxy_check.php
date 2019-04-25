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

    $proxy_str = '103.42.213.176	8080	
  Hong Kong Central
800 мс

HTTP	Высокая	1 минута
139.59.61.229	3128	
  India Bengaluru
800 мс

HTTP	Высокая	1 минута
139.59.64.9	3128	
  India Bengaluru
1440 мс

HTTP	Высокая	1 минута
186.120.238.120	8081	
  Dominican Republic "Santo Domingo Este"
980 мс

HTTP	Низкая	1 минута
150.246.237.92	3128	
  Japan Himeji
600 мс

HTTP, HTTPS	Нет	1 минута
199.21.96.47	80	
  United States
200 мс

HTTP	Нет	1 минута
199.21.97.220	80	
  United States "San Jose"
220 мс

HTTP	Нет	1 минута
157.230.232.130	80	
  United States "North Bergen"
540 мс

HTTP	Средняя	1 минута
142.93.96.177	8080	
  Germany "Frankfurt am Main"
220 мс

HTTP	Средняя	1 минута

139.59.62.255	8080	
  India Bengaluru
1360 мс

HTTP	Высокая	1 минута
46.235.71.241	8080	
  Russian Federation Moscow
1220 мс

HTTP	Высокая	1 минута
199.21.96.16	80	
  United States
200 мс

HTTP	Нет	1 минута
82.196.11.105	3128	
  Netherlands Amsterdam
520 мс

HTTP	Высокая	1 минута
199.21.98.12	80	
  United States
200 мс

HTTP	Нет	1 минута
110.164.58.106	8082	
  Thailand Bangkok
1140 мс

HTTP	Нет	1 минута
157.230.149.54	80	
  United States "Santa Clara"
820 мс

HTTP	Средняя	1 минута
47.74.15.236	3128	
  Japan Tokyo
1100 мс

HTTPS	Высокая	1 минута
157.230.210.133	8080	
  United States "North Bergen"
440 мс

HTTP	Средняя	1 минута
192.166.219.46	3128	
  Poland
260 мс

HTTP, HTTPS	Нет	1 минута

163.172.153.70	8080	
  France
260 мс

HTTP	Высокая	1 минута
199.21.96.83	80	
  United States
160 мс

HTTP	Нет	1 минута
199.21.99.35	80	
  United States
220 мс

HTTP	Нет	1 минута
199.21.99.27	80	
  United States
200 мс

HTTP	Нет	1 минута
138.201.193.196	8080	
  Germany
520 мс

HTTP, HTTPS	Средняя	2 минуты

';

    preg_match_all('/(\d+\.\d+\.\d+\.\d+)\s+(\d+)/', $proxy_str, $matches);
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
