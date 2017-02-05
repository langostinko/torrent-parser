<?php

class ProxyFinder {
    private static $cache = array();
    
    static private function getProxyList() {
        static $list = null;
        if (!$list)
            $list = array("206.78.234.134:8080", "60.250.81.118:8080", "117.135.250.88:80");
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
        $host = parse_url($url)['host'];
        if (!array_key_exists($host, ProxyFinder::$cache))
            ProxyFinder::$cache[$host] = ProxyFinder::getNextProxy(null);
        if (ProxyFinder::$cache[$host] && $curProxy == ProxyFinder::$cache[$host])
            ProxyFinder::$cache[$host] = ProxyFinder::getNextProxy($curProxy);
        return ProxyFinder::$cache[$host];
    }
}

?>