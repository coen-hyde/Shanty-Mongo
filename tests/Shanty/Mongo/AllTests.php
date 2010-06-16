<?php

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/Mongo/CollectionTest.php';
require_once 'Shanty/Mongo/DocumentTest.php';
require_once 'Shanty/Mongo/DocumentSetTest.php';
require_once 'Shanty/Mongo/Iterator/CursorTest.php';
require_once 'Shanty/Mongo/Iterator/DefaultTest.php';
require_once 'Shanty/Mongo/Validate/ArrayTest.php';
require_once 'Shanty/Mongo/Validate/ClassTest.php';
require_once 'Shanty/Mongo/Validate/StubTrueTest.php';
require_once 'Shanty/Mongo/Connection/StackTest.php';
require_once 'Shanty/Mongo/Connection/GroupTest.php';

class Shanty_Mongo_AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('Shanty Mongo - Shanty_Mongo');
 
		$suite->addTestSuite('Shanty_Mongo_CollectionTest');
		$suite->addTestSuite('Shanty_Mongo_DocumentTest');
		$suite->addTestSuite('Shanty_Mongo_DocumentSetTest');
		$suite->addTestSuite('Shanty_Mongo_Iterator_CursorTest');
		$suite->addTestSuite('Shanty_Mongo_Iterator_DefaultTest');
		$suite->addTestSuite('Shanty_Mongo_Validate_ArrayTest');
		$suite->addTestSuite('Shanty_Mongo_Validate_ClassTest');
		$suite->addTestSuite('Shanty_Mongo_Validate_StubTrueTest');
		$suite->addTestSuite('Shanty_Mongo_Connection_StackTest');
		$suite->addTestSuite('Shanty_Mongo_Connection_GroupTest');
 
		return $suite;
	}
}