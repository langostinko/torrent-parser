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
            ProxyFinder::$list = array("59.124.224.180:4378","59.124.224.180:3128","195.154.207.39:3128","92.244.99.229:3128","190.242.98.61:8083","178.212.54.137:8080", 
            "45.71.184.170:999","121.254.171.246:808","186.0.137.242:3128","5.189.133.231:80");
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