<?php

class ProxyFinder {
    private static $cache = array();
    
    static private function getProxyList() {
        static $list = null;
        if (!$list)
            $list = array("178.128.96.106:8080", "85.220.253.95:3128", "217.69.245.69:53281", "34.205.174.79:3128");
        return $list;
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
}

?>