<?php

class ProxyFinder {
    private static $cache = array();
    private static $list = null;

    static public function setProxyList($list) {
        ProxyFinder::$list = $list;
        ProxyFinder::$cache = array();
    }
    
    static public function getCache() {
        return ProxyFinder::$cache;
    }
    
    static public function findProxy($url, $curProxy) {
        global $logger;
        $host = parse_url($url)['host'];
        if (!array_key_exists($host, ProxyFinder::$cache))
            ProxyFinder::$cache[$host] = ProxyFinder::getNextProxy(null);
        if (ProxyFinder::$cache[$host] && $curProxy == ProxyFinder::$cache[$host])
            ProxyFinder::$cache[$host] = ProxyFinder::getNextProxy($curProxy);
        if (!ProxyFinder::$cache[$host]) {
            $logger->warning("no proxy for " . $url);
            // unset(ProxyFinder::$cache[$host]); //RETRY other time
            return false;
        }
        return ProxyFinder::$cache[$host];
    }

    static private function getProxyList() {
        if (!ProxyFinder::$list)
            ProxyFinder::$list = array("103.42.213.176:8080","139.59.61.229:3128","139.59.64.9:3128","186.120.238.120:8081","150.246.237.92:3128","157.230.232.130:80","142.93.96.177:8080","139.59.62.255:8080","110.164.58.106:8082","157.230.149.54:80","157.230.210.133:8080","192.166.219.46:3128","163.172.153.70:8080","138.201.193.196:8080");
        return ProxyFinder::$list;
    }

    static private function getNextProxy($proxy) {
        $list = ProxyFinder::getProxyList();
        if (!$proxy)
            $i = 0;
        else
            $i = array_search($proxy, $list) + 1;
        if ($i >= count($list))
            return false;
        return $list[$i];
    }

}

?>