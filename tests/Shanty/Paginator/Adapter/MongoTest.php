<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Paginator/Adapter/Mongo.php';
require_once 'Zend/Paginator.php';

class Shanty_Paginator_Adapter_MongoTest extends Shanty_Paginator_TestSetup
{
	public function testPaginator()
	{
		$countries = My_ShantyMongo_Country::all();
		$this->assertEquals(239, $countries->count());

		$paginator = new Zend_Paginator(new Shanty_Paginator_Adapter_Mongo($countries));
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber(3);
		
		$this->assertEquals(24, $paginator->count()); // Count pages
		$this->assertEquals(239, $paginator->getTotalItemCount()); // count total items
		$this->assertEquals(10, $paginator->getCurrentItemCount()); // count items on this page

		$paginator->getCurrentItems()->rewind();
		$firstItem = $paginator->getCurrentItems()->current();

		$this->assertEquals($firstItem->code, 'BB');
		$this->assertEquals($firstItem->name, 'Barbados');
	}
}