<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_Name extends My_ShantyMongo_Abstract
{
	protected static $_requirements = array(
		'first' => 'Required',
		'last' => 'Required'
	);
	
	public $_hookCounter = array(
		'init' => 0,
		'preInsert' => 0,
		'postInsert' => 0,
		'preUpdate' => 0,
		'postUpdate' => 0,
		'preSave' => 0,
		'postSave' => 0
	);
	
	public function full()
	{
		return $this->first.' '.$this->last;
	}
	
	public function __toString()
	{
		return $this->full();
	}
	
	protected function init()
	{
		$this->_hookCounter['init'] += 1;
	}
	
	protected function preInsert()
	{
		$this->_hookCounter['preInsert'] += 1;
	}
	
	protected function postInsert()
	{
		$this->_hookCounter['postInsert'] += 1;
	}
	
	protected function preUpdate()
	{
		$this->_hookCounter['preUpdate'] += 1;
	}
	
	protected function postUpdate()
	{
		$this->_hookCounter['postUpdate'] += 1;
	}
	
	protected function preSave()
	{
	    $this->_hookCounter['preSave'] += 1;
	}
	
	protected function postSave()
	{
		$this->_hookCounter['postSave'] += 1;
	}
}