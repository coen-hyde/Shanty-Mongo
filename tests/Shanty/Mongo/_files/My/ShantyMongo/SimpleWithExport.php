<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_SimpleWithExport extends My_ShantyMongo_Abstract
{
	protected static $_requirements = array(
		'myIgnoredProperty' => 'Ignore',
		'subDoc' => array('Document:My_ShantyMongo_SimpleSubDocWithExport')
	);
	
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'simple';
	
	public function export() {
		$data = parent::export();
		$data['myIgnoredProperty'] = 'some data';
		$data['myUnignoredProperty'] = 'some data';
		return $data;
	}
}