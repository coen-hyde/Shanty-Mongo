<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'Shanty/Mongo/Validate/StubTrue.php';

class Shanty_Mongo_Validate_StubTrueTest extends Shanty_Mongo_TestSetup
{
	public function testIsValid()
	{
		$validator = new Shanty_Mongo_Validate_StubTrue();
		$this->assertTrue($validator->isValid($this->getMock('My_ShantyMongo_User')));
		$this->assertTrue($validator->isValid(42));
	}
}