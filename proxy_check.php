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

    $proxy_str = '"173.165.143.157:60920","188.163.170.130:41209","168.194.250.4:23500","195.77.80.154:42402","168.232.167.238:3128","78.107.209.229:7016","78.107.209.229:7012","78.107.209.229:7011","45.120.112.65:8080","110.78.153.211:3128","171.100.9.126:49163","203.150.150.95:8080","182.19.41.145:80","27.72.60.234:8080","200.188.151.212:8080","190.143.216.210:8080","24.245.100.212:48678","200.48.192.3:8080","154.214.138.45:3128","187.60.188.70:3128","46.21.253.54:88","89.208.20.250:34350","182.73.214.78:8080","192.241.245.207:8080","183.88.212.141:8080","177.54.130.130:40909","209.97.152.252:8080","157.230.0.117:80","83.143.31.254:8888","185.188.218.10:60928"';

    preg_match_all('/(\d+\.\d+\.\d+\.\d+)[\s\:]+(\d+)/', $proxy_str, $matches);
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
