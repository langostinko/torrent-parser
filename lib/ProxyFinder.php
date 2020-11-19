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
            ProxyFinder::$list = array("162.243.108.161:8080","204.93.196.148:8080","34.67.66.107:8080","35.225.66.85:8080","198.245.62.122:8080","164.68.117.9:8080","67.205.149.230:8080","207.154.231.211:8080","82.196.11.105:8080","138.68.161.14:8080","207.154.231.217:8080","138.68.165.154:8080","198.199.120.102:8080","207.154.231.216:8080","139.59.169.246:8080","162.243.108.141:8080","178.62.193.19:8080","67.205.146.29:8080","138.197.157.45:8080","217.23.6.40:8080","46.4.96.137:8080","88.198.50.103:8080","88.198.24.108:8080","185.132.133.203:8080","207.154.231.212:8080","138.68.173.29:8080","145.239.81.69:8080","188.226.141.211:8080","188.226.141.127:8080","176.9.119.170:8080","176.9.75.42:8080","94.177.214.178:8080","217.61.122.19:8080","80.211.72.92:8080","176.105.252.143:3128","176.105.252.143:8080","185.122.252.122:8080","124.156.108.71:82","139.59.62.255:3128","187.162.11.94:3128");
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