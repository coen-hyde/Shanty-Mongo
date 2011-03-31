<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';

class Shanty_Mongo_ConnectionTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
	}
	
	public function testGetAvailableOptions()
	{
		$options = array(
			'persist',
			'timeout',
			'replicaSet'
		);
		$this->assertEquals($options, Shanty_Mongo_Connection::getAvailableOptions());
	}
}