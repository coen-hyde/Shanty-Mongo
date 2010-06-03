<?php

require_once 'Shanty/Mongo/Document.php';

class My_ShantyMongo_Name extends Shanty_Mongo_Document
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