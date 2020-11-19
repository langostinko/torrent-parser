<?php
    require_once __DIR__."/lib/lib.php";

    function main_callback($response, $info, $request) {
        // echo "proxy cache";
        // print_r(ProxyFinder::getCache());
        // echo "request";
        // print_r($request);
        // echo "info";
        // print_r($info);
        // echo "response";
        // print_r($response);
    }

    $proxy_str = '59.124.224.180	4378	Taiwan Taipei	
1160 ms

HTTP	no	1 minutes
59.124.224.180	3128	Taiwan Taipei';

    preg_match_all('/(\d+\.\d+\.\d+\.\d+)[\s\:]+(\d+)/', $proxy_str, $matches);
    $proxy_list = array();
    for ($i = 0; $i < count($matches[1]); ++$i) {
        $proxy_list[] = $matches[1][$i] . ":" . $matches[2][$i];
    }

    $good_proxy_list = array();
    while (count($proxy_list)) {
        print_r($proxy_list);
        ProxyFinder::setProxyList($proxy_list);
        $link = "http://rutor.info/";
        RollingCurl::$rc = new RollingCurl("main_callback");
        RollingCurl::$rc->get($link, null, null, null );
        RollingCurl::$rc->execute();
        $good_proxy = ProxyFinder::getCache()['rutor.info'];
        if ($good_proxy) {
            echo "good proxy: " . $good_proxy . " : " . count($proxy_list) . " proxies left\n";
            $good_proxy_list[] = $good_proxy;
            $offset = array_search($good_proxy, $proxy_list) + 1;
            $proxy_list = array_slice($proxy_list, $offset);
        } else {
            $proxy_list = array();
        }
    }
    echo "good_proxy_list: " . join('","', $good_proxy_list);
?>
