<?php

require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'Shanty/Paginator/Adapter/MongoTest.php';

class Shanty_Paginator_AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('Shanty Mongo - Shanty_Paginator');

		$suite->addTestSuite('Shanty_Paginator_Adapter_MongoTest');

		return $suite;
	}
}