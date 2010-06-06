<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'Shanty/Mongo/Validate/Class.php';

class Shanty_Mongo_Validate_ClassTest extends Shanty_Mongo_TestSetup
{
	public function testIsValid()
	{
		$validator = new Shanty_Mongo_Validate_Class('My_ShantyMongo_User');
		$this->assertTrue($validator->isValid($this->getMock('My_ShantyMongo_User')));
		$this->assertFalse($validator->isValid(42));
	}
}