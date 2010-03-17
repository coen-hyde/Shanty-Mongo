<?php

class Shanty_Mongo_Validate_Class extends Zend_Validate_Abstract
{
	/**
     * @var array
     */
	protected $_messageTemplates = array(
		'class' => "'%value%' is not a %class%"
	);
	
    /**
     * @var array
     */
    protected $_messageVariables = array(
        'class' => '_class'
    );

	protected $_class = null;

	public function __construct($class) 
	{
		$this->setClass($class);
	}

	public function setClass($class)
	{
		$this->_class = $class;
	}
	
	public function getClass()
	{
		return $this->_class;
	}

	public function isValid($value)
	{
		$this->_setValue($value);
		$class = $this->getClass();
		
		if (!($value instanceof $class)) {
			$this->_error();
			return false;
		}
		
		return true;
	}
}