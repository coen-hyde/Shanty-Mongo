<?php

require_once 'My/ShantyMongo/User.php';

class My_ShantyMongo_Student extends My_ShantyMongo_User
{
	protected static $_requirements = array(
		'concession' => 'Required'
	);
}