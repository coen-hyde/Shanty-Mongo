<?php
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo.php';
require_once 'Shanty/Mongo/Connection.php';
 
class Shanty_Mongo_TestSetup extends PHPUnit_Framework_TestCase
{
	protected $_runtimeIncludePath = null;
	protected $_filesDir = '';
	protected $_connection = null;
	
	public function setUp()
	{
		$this->_useMyIncludePath();

		require_once 'My/ShantyMongo/User.php';
		require_once 'My/ShantyMongo/Users.php';
		require_once 'My/ShantyMongo/Name.php';
		require_once 'My/ShantyMongo/Student.php';
		
		$this->_connection = new Shanty_Mongo_Connection(TESTS_SHANTY_MONGO_CONNECTIONSTRING);
		$this->_connection->connect();
		Shanty_Mongo::addMaster($this->_connection);
		
		$this->populateDb();
		usleep(50000);
	}
	
	public function populateDb()
	{
		$this->_users = array(
			'bob' => array(
				'_id' => new MongoId('4c04516a1f5f5e21361e3ab0'),
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
				'email' => 'bob.jones@domain.com',
				'sex' => 'M',
				'partner' => MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
				'bestFriend' => MongoDBRef::create('user', new MongoId('4c0451791f5f5e21361e3ab2'))
			),
			'cherry' => array(
				'_id' => new MongoId('4c04516f1f5f5e21361e3ab1'),
				'name' => array(
					'first' => 'Cherry',
					'last' => 'Jones',
				),
				'email' => 'cherry.jones@domain.com',
				'sex' => 'F',
//				'bestFriend' => MongoDBRef::create('user', new MongoId('broken reference'))
			),
			'roger' => array(
				'_id' => new MongoId('4c0451791f5f5e21361e3ab2'),
				'name' => array(
					'first' => 'Roger',
					'last' => 'Smith',
				),
				'email' => 'roger.smith@domain.com',
				'sex' => 'M'
			),
		);
		
		$userCollection = $this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user');
		
		foreach ($this->_users as $user) {
			$userCollection->insert($user);
		}
	}
	
	public function tearDown()
	{
		$this->_restoreIncludePath();

		$this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user')->drop();
		
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