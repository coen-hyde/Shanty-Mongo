<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/AllTests.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Shanty Mongo');
 
        $suite->addTest(Shanty_AllTests::suite());
 
        return $suite;
    }
}