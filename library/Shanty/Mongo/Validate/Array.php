<?php

require_once 'Zend/Validate/Abstract.php';

class Shanty_Mongo_Validate_Array extends Zend_Validate_Abstract
{
	const NOT_ARRAY = 'notArray';
	
	protected $_messageTemplates = array(
		self::NOT_ARRAY => "Value is not an Array"
	);

	public function isValid($value)
	{
		if (!is_array($value)) {
			$this->_error(self::NOT_ARRAY);
			return false;
		}
		
		return true;
	}
}