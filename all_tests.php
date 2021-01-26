<?php
require_once 'lib/defines.php';
require_once 'lib/core_tests.php';
require_once 'lib/loaders/GooglePlayLoaderTests.php';

use PHPUnit\Framework\TestSuite;

class AllTests
{
    public static function suite()
    {
        $suite = new TestSuite('MySuite');
        // добавляем тест в набор
        $suite->addTestSuite('CoreTests'); 
        $suite->addTestSuite('GooglePlayLoaderTests');
        return $suite; 
    }
}
?>
