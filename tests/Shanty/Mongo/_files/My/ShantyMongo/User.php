<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_User extends My_ShantyMongo_Abstract
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'user';

	protected static $_requirements = array(
		'name' => array('Document:My_ShantyMongo_Name', 'Required'),
		'email' => array('Required', 'Validator:EmailAddress'),
		'addresses' => 'DocumentSet',
		'addresses.$.street' => 'Required',
		'addresses.$.state' => 'Required',
		'addresses.$.suburb' => 'Required',
		'addresses.$.postcode' => 'Required',
		'friends' => 'DocumentSet:My_ShantyMongo_Users',
		'friends.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'sex' => array('Required', 'Validator:InArray' => array('F', 'M')),
		'partner' => array('Document:My_ShantyMongo_User', 'AsReference')
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

        public $_shardKey = 'email';

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