<?php
require_once 'lib/core_tests.php';

use PHPUnit\Framework\TestSuite;

class AllTests
{
    public static function suite()
    {
        $suite = new TestSuite('MySuite');
        // добавляем тест в набор
        $suite->addTestSuite('CoreTests'); 
        return $suite; 
    }
}
?>
