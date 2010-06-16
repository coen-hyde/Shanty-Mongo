<?php

require_once 'Shanty/Mongo/Document.php';

class My_ShantyMongo_Article extends Shanty_Mongo_Document
{
	protected static $_db = TESTS_SHANTY_MONGO_DB;
	protected static $_collection = 'article';

	protected static $_requirements = array(
		'title' => array('Required', 'Filter:StringTrim'),
		'author' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'contributors' => 'DocumentSet:My_ShantyMongo_Users',
		'contributors.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
		'relatedArticles' => 'DocumentSet',
		'relatedArticles.$' => 'Document:My_ShantyMongo_Article',
		'tags' => 'Array'
	);
}