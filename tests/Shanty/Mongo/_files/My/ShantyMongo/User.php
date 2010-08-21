<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_User extends My_ShantyMongo_Abstract
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'user';

	protected static $_requirements = array(
		'name' => 'Document:My_ShantyMongo_Name',
		'email' => 'Validator:EmailAddress',
		'addresses' => array('DocumentSet', 'Optional'),
		'addresses.$.street',
		'addresses.$.state',
		'addresses.$.suburb',
		'addresses.$.postcode',
		'friends' => array('DocumentSet:My_ShantyMongo_Users', 'Optional'),
		'friends.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'sex' => array('Validator:InArray' => array('F', 'M')),
		'partner' => array('Document:My_ShantyMongo_User', 'AsReference', 'Optional')
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