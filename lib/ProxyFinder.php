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
            ProxyFinder::$list = array("170.81.35.26:36681","14.140.193.90:8080","118.27.20.17:3128","187.16.4.108:8080","14.140.193.89:8080","68.183.99.243:80","142.93.24.225:80");
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