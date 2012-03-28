<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_Admin extends My_ShantyMongo_Abstract
{
	protected static $_db = 'admin';
	protected static $_collection = 'admin';
}