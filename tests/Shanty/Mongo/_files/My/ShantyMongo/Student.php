<?php

require_once 'My/ShantyMongo/User.php';

class My_ShantyMongo_Student extends My_ShantyMongo_User
{
	protected static $_collectionRequirements = array(
		'concession' => 'Required'
	);
}