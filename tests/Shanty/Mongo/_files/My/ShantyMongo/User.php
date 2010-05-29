<?php

require_once 'Shanty/Mongo/Document.php';

class My_ShantyMongo_User extends Shanty_Mongo_Document
{
	protected static $_dbName = TESTS_SHANTY_MONGO_DB;
	protected static $_collectionName = 'user';
	protected static $_documentSetClass = 'My_ShantyMongo_Users';

	protected static $_collectionRequirements = array(
		'name' => array('Document:My_ShantyMongo_Name', 'Required'),
		'email' => array('Required', 'Validator:EmailAddress'),
		'addresses' => 'DocumentSet',
		'addresses.$.state' => 'Required',
		'addresses.$.suburb' => 'Required',
		'addresses.$.postCode' => 'Required',
		'friends' => 'DocumentSet',
		'friends.$' => array('Document:User', 'AsReference'),
		'sex' => array('Required', 'Validator:InArray' => array('female', 'male')),
		'partner' => array('Document:User', 'AsReference')
	);
}