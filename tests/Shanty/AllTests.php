<?php

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/MongoTest.php';
require_once 'Shanty/Mongo/AllTests.php';

class Shanty_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Shanty Mongo - Shanty');
 
        $suite->addTestSuite('Shanty_MongoTest');
        $suite->addTest(Shanty_Mongo_AllTests::suite());
 
        return $suite;
    }
}