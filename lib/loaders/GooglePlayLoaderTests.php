<?php
require_once "GooglePlayLoader.php";

use PHPUnit\Framework\TestCase;

final class GooglePlayLoaderTests extends TestCase
{
    public function testHttpCode500(): void
    {
        global $logger;
        $loader = new GooglePlayLoader("");
        $loader->setLogger($logger);
        $loader->getGooglePlayCallback("", array("http_code" => 500, "url" => "", "total_time" => 0));
        $result = $loader->getResult();
        $this->assertEquals(count($result), 0);
    }

}
?>