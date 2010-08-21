<?php

require_once 'My/ShantyMongo/Abstract.php';

class My_ShantyMongo_Article extends My_ShantyMongo_Abstract
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'article';

	protected static $_requirements = array(
		'title' => 'Filter:StringTrim',
		'author' => array('Document:My_ShantyMongo_User', 'AsReference', 'Optional'),
		'contributors' => array('DocumentSet:My_ShantyMongo_Users', 'Optional'),
		'contributors.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'relatedArticles' => array('DocumentSet', 'Optional'),
		'relatedArticles.$' => 'Document:My_ShantyMongo_Article',
		'tags' => array('Array', 'Optional')
	);
}