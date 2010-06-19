<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_Name extends My_ShantyMongo_Abstract
{
	protected static $_requirements = array(
		'first' => 'Required',
		'last' => 'Required'
	);
	
	public function full()
	{
		return $this->first.' '.$this->last;
	}
	
	public function __toString()
	{
		return $this->full();
	}
}