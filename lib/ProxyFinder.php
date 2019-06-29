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
            ProxyFinder::$list = array("173.165.143.157:60920","168.194.250.4:23500","168.232.167.238:3128","45.120.112.65:8080","171.100.9.126:49163","182.19.41.145:80","27.72.60.234:8080","200.188.151.212:8080","190.143.216.210:8080","187.60.188.70:3128","89.208.20.250:34350","182.73.214.78:8080","192.241.245.207:8080","183.88.212.141:8080","209.97.152.252:8080","157.230.0.117:80","83.143.31.254:8888");
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