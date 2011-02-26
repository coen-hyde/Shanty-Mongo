<?php
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo.php';
require_once 'Shanty/Mongo/Connection.php';

require_once 'Zend/Validate.php';
require_once 'Zend/Validate/EmailAddress.php';
require_once 'Zend/Validate/InArray.php';
require_once 'Zend/Validate/Hostname.php';

require_once 'Zend/Filter.php';
require_once 'Zend/Filter/StringToUpper.php';
require_once 'Zend/Filter/StringTrim.php';
require_once 'Zend/Filter/Alpha.php';

 
class Shanty_Mongo_TestSetup extends PHPUnit_Framework_TestCase
{
	protected $_runtimeIncludePath = null;
	protected $_filesDir = '';
	protected $_connection = null;
	protected $_userCollection = null;
	protected $_articleCollection = null;
	
	public function setUp()
	{
		$this->_useMyIncludePath();

		require_once 'My/ShantyMongo/User.php';
		require_once 'My/ShantyMongo/Users.php';
		require_once 'My/ShantyMongo/Name.php';
		require_once 'My/ShantyMongo/Student.php';
		require_once 'My/ShantyMongo/ArtStudent.php';
		require_once 'My/ShantyMongo/Teacher.php';
		require_once 'My/ShantyMongo/Article.php';
		require_once 'My/ShantyMongo/InvalidDocument.php';
		require_once 'My/ShantyMongo/Simple.php';
		
		$this->_connection = new Shanty_Mongo_Connection(TESTS_SHANTY_MONGO_CONNECTIONSTRING);
		$this->_connection->connect();
		Shanty_Mongo::addMaster($this->_connection);
		
		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user')->drop();
		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('article')->drop();
		$this->populateDb();
	}
	
	public function populateDb()
	{
		$this->_users = array(
			'bob' => array(
				'_id' => new MongoId('4c04516a1f5f5e21361e3ab0'),
				'_type' => array(
					'My_ShantyMongo_Teacher',
					'My_ShantyMongo_User'
				),
				'name' => array(
					'first' => 'Bob',
					'last' => 'Jones',
				),
				'addresses' => array(
					array(
						'street' => '19 Hill St',
						'suburb' => 'Brisbane',
						'state' => 'QLD',
						'postcode' => '4000',
						'country' => 'Australia'
					),
					array(
						'street' => '742 Evergreen Terrace',
						'suburb' => 'Springfield',
						'state' => 'Nevada',
						'postcode' => '89002',
						'country' => 'USA'
					)
				),
				'friends' => array(
					MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
					MongoDBRef::create('user', new MongoId('4c0451791f5f5e21361e3ab2')),
					MongoDBRef::create('user', new MongoId('broken reference'))
				),
				'faculty' => 'Maths',
				'email' => 'bob.jones@domain.com',
				'sex' => 'M',
				'partner' => MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
				'bestFriend' => MongoDBRef::create('user', new MongoId('4c0451791f5f5e21361e3ab2')) // To test Shanty_Mongo_Document::isReference()
			),
			'cherry' => array(
				'_id' => new MongoId('4c04516f1f5f5e21361e3ab1'),
				'_type' => array(
					'My_ShantyMongo_Student',
					'My_ShantyMongo_User'
				),
				'name' => array(
					'first' => 'Cherry',
					'last' => 'Jones',
				),
				'email' => 'cherry.jones@domain.com',
				'sex' => 'F',
				'concession' => true
			),
			'roger' => array(
				'_id' => new MongoId('4c0451791f5f5e21361e3ab2'),
				'_type' => array(
					'My_ShantyMongo_ArtStudent',
					'My_ShantyMongo_Student',
					'My_ShantyMongo_User'
				),
				'name' => array(
					'first' => 'Roger',
					'last' => 'Smith',
				),
				'email' => 'roger.smith@domain.com',
				'sex' => 'M',
				'concession' => false
			),
		);
		
		$this->_userCollection = $this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user');
		
		foreach ($this->_users as $user) {
			$this->_userCollection->insert($user, true);
		}
		
		$this->_articles = array(
			'regular' => array(
				'_id' => new MongoId('4c04516f1f5f5e21361e3ac1'),
				'title' => 'How to use Shanty Mongo',
				'author' => MongoDBRef::create('user', new MongoId('4c04516a1f5f5e21361e3ab0')),
				'editor' => MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
				'contributors' => array(
					MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
					MongoDBRef::create('user', new MongoId('4c0451791f5f5e21361e3ab2')),
				),
				'relatedArticles' => array(
					MongoDBRef::create('article', new MongoId('4c04516f1f5f5e21361e3ac2')),
				),
				'tags' => array('awesome', 'howto', 'mongodb')
			),
			'broken' => array(
				'_id' => new MongoId('4c04516f1f5f5e21361e3ac2'),
				'title' => 'How to use Bend Space and Time',
				'author' => MongoDBRef::create('user', new MongoId('broken_reference')),
				'tags' => array('physics', 'hard', 'cool')
			)
		);
		
		$this->_articleCollection = $this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('article');
		
		foreach ($this->_articles as $article) {
			$this->_articleCollection->insert($article, true);
		}
	}
	
	public function tearDown()
	{
		$this->_restoreIncludePath();

		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user')->drop();
		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('article')->drop();
		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('simple')->drop();

		Shanty_Mongo::makeClean();
		
		parent::tearDown();
	}
	
	protected function _useMyIncludePath()
	{
		$this->_runtimeIncludePath = get_include_path();
		set_include_path(dirname(__FILE__) . '/_files/' . PATH_SEPARATOR . $this->_runtimeIncludePath);
	}
	
	protected function _restoreIncludePath()
	{
		set_include_path($this->_runtimeIncludePath);
		$this->_runtimeIncludePath = null;
	}
	
	protected function getFilesDir()
	{
		return __DIR__ . '_files';
	}
}