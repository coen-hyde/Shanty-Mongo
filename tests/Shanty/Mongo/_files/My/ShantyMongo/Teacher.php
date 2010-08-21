<?php

require_once 'My/ShantyMongo/User.php';

class My_ShantyMongo_Teacher extends My_ShantyMongo_User
{
	protected static $_requirements = array(
		'faculty'
	);
}