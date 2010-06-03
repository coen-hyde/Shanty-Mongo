<?php

require_once 'Shanty/Mongo/Document.php';

class My_ShantyMongo_User extends Shanty_Mongo_Document
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'user';

	protected static $_requirements = array(
		'name' => array('Document:My_ShantyMongo_Name', 'Required'),
		'email' => array('Required', 'Validator:EmailAddress'),
		'addresses' => 'DocumentSet',
		'addresses.$.state' => 'Required',
		'addresses.$.suburb' => 'Required',
		'addresses.$.postCode' => 'Required',
		'friends' => 'DocumentSet:My_ShantyMongo_Users',
		'friends.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'sex' => array('Required', 'Validator:InArray' => array('female', 'male')),
		'partner' => array('Document:User', 'AsReference')
	);
}