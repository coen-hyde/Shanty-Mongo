<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_Simple extends My_ShantyMongo_Abstract
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'simple';
}
?>