<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'Shanty/Mongo/Validate/Array.php';

class Shanty_Mongo_Validate_ArrayTest extends Shanty_Mongo_TestSetup
{
	public function testIsValid()
	{
		$validator = new Shanty_Mongo_Validate_Array();
		$this->assertTrue($validator->isValid(array()));
		$this->assertFalse($validator->isValid(42));
	}
}