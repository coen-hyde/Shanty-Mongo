<?php

class Shanty_Mongo_Validate_Numeric extends Zend_Validate_Abstract
{
	public function isValid($value)
	{
        if(is_numeric($value))
            return true;

		return false;
	}
}