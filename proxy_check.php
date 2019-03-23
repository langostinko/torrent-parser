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

    $proxy_str = '190.211.115.66  57668   
  Costa Rica Sarapiqui
1020 мс

HTTP    Высокая 1 минута
190.14.154.86   8080    
  Costa Rica "San JosÃ©"
1020 мс

HTTP    Нет 1 минута
170.81.35.26    36681   
  Costa Rica Heredia
1020 мс

HTTP    Высокая 1 минута
14.140.193.90   8080    
  India Gurgaon
880 мс

HTTP, HTTPS Нет 1 минута
118.27.20.17    3128    
  Japan Shibuya
1240 мс

HTTP    Нет 1 минута
187.16.4.108    8080    
  Brazil "SÃ£o Paulo"
1100 мс

HTTP    Нет 1 минута
14.140.193.89   8080    
  India Gurgaon
920 мс

HTTP, HTTPS Нет 1 минута
68.183.99.243   80  
  United States "North Bergen"
540 мс

HTTP    Средняя 1 минута
142.93.24.225   80  
  United States "Santa Clara"
800 мс

HTTP    Средняя 1 минута';

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
