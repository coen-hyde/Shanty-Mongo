<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_SimpleSubDocWithExport extends My_ShantyMongo_Abstract
{
	protected static $_requirements = array(
		'myIgnoredProperty' => 'Ignore',
		'subDoc' => array('Document:My_ShantyMongo_SimpleSubDocWithExport')
	);
	
	public function export($skipRequired = false) {
		$data = parent::export($skipRequired);
		$data['myIgnoredProperty'] = 'sub document data';
		$data['myUnignoredProperty'] = 'sub document data';
		return $data;
	}
}