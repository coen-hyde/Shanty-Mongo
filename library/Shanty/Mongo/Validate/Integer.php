<?php

class Shanty_Mongo_Validate_Integer extends Zend_Validate_Abstract
{
	public function isValid($value)
	{
        if(is_integer($value))
            return true;

		return false;
	}
}