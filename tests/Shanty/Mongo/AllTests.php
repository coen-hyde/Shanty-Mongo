<?php

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/Mongo/TestCollection.php';
require_once 'Shanty/Mongo/Connection/StackTest.php';
require_once 'Shanty/Mongo/Connection/GroupTest.php';

class Shanty_Mongo_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Shanty Mongo - Shanty_Mongo');
 
        $suite->addTestSuite('Shanty_Mongo_TestCollection');
        $suite->addTestSuite('Shanty_Mongo_Connection_StackTest');
        $suite->addTestSuite('Shanty_Mongo_Connection_GroupTest');
 
        return $suite;
    }
}