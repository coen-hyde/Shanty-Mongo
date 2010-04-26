<?php

require_once 'Zend/Validate/Abstract.php';

class Shanty_Mongo_Validate_Array extends Zend_Validate_Abstract
{
	protected $_messageTemplates = array(
		'array' => "Value is not an Array"
		);

	public function isValid($value)
	{
		if (!is_array($value)) {
			$this->_error();
			return false;
		}
		
		return true;
	}
}