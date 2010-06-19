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
}