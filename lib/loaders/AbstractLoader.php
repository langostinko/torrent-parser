<?php
abstract class AbstractLoader
{
    protected $logger;
    protected $result;
    
    abstract public function load();
    public function setLogger($logger) {
        $this->logger = $logger;
    }
    
    function getResult() {
        return (array)($this->result);
    }
}
?>