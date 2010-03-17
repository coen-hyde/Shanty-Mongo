<?php

class Shanty_Mongo_Validate_StubTrue extends Zend_Validate_Abstract
{
	public function isValid($value)
	{
		return true;
	}
}