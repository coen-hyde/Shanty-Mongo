<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/Mongo/AllTests.php';

class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Shanty_Mongo');
 
        $suite->addTest(Shanty_Mongo_AllTests::suite());
 
        return $suite;
    }
}