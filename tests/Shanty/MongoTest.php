<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Mongo' . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'Shanty/Mongo.php';
require_once 'Zend/Config.php';

class Shanty_MongoTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		Shanty_Mongo::makeClean();
		Shanty_Mongo::init();
	}
	
	public function testMakeClean()
	{
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroups()));
		Shanty_Mongo::setConnectionGroup('users', new Shanty_Mongo_Connection_Group());
		Shanty_Mongo::makeClean();
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroups()));
	}
	
	public function testConnectionGroups()
	{
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, Shanty_Mongo::getConnectionGroups());
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroups()));
		
		$connectionGroup = new Shanty_Mongo_Connection_Group();
		Shanty_Mongo::setConnectionGroup('users', $connectionGroup);
		$this->assertEquals(1, count(Shanty_Mongo::getConnectionGroups()));
		$this->assertEquals(Shanty_Mongo::getConnectionGroup('users'), $connectionGroup);
		
		$this->assertFalse(Shanty_Mongo::hasConnectionGroup('accounts'));
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, Shanty_Mongo::getConnectionGroup('accounts'));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroups()));
		
		$this->assertTrue(Shanty_Mongo::hasConnectionGroup('accounts'));
	}
	
	public function testAddMaster()
	{
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addMaster($connection);
		$this->assertEquals($connection, Shanty_Mongo::getConnectionGroup('default')->getWriteConnection());
	}
	
	public function testAddSlave()
	{
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addSlave($connection);
		$this->assertEquals($connection, Shanty_Mongo::getConnectionGroup('default')->getReadConnection());
	}
	
	/**
	 * @depends testAddMaster
	 */
	public function testGetWriteConnection()
	{
		$connection = Shanty_Mongo::getWriteConnection();
		$this->assertNotNull($connection);
		$connectionInfo = $connection->getConnectionInfo();
		$this->assertEquals('127.0.0.1', $connectionInfo['connectionString']);

		Shanty_Mongo::removeConnectionGroups();
		
		$connection = $this->getMock('Shanty_Mongo_Connection');
		Shanty_Mongo::addMaster($connection);
		$this->assertEquals($connection, Shanty_Mongo::getWriteConnection());
		
		Shanty_Mongo::removeConnectionGroups();
		
		$connection = $this->getMock('Shanty_Mongo_Connection');
		Shanty_Mongo::addMaster($connection, 1, 'users');
		$this->assertEquals($connection, Shanty_Mongo::getWriteConnection('users'));
	}
	
	/**
	 * @depends testGetWriteConnection
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testNoWriteConnections()
	{
		Shanty_Mongo::getWriteConnection('users');
	}
	
	/**
	 * @depends testAddSlave
	 */
	public function testGetReadConnection()
	{
		$connection = Shanty_Mongo::getReadConnection();
		$this->assertNotNull($connection);
		$this->assertEquals($connection, Shanty_Mongo::getWriteConnection());
		$connectionInfo = $connection->getConnectionInfo();
		$this->assertEquals('127.0.0.1', $connectionInfo['connectionString']);

		Shanty_Mongo::removeConnectionGroups();
		
		$connection = $this->getMock('Shanty_Mongo_Connection');
		Shanty_Mongo::addSlave($connection);
		$this->assertEquals($connection, Shanty_Mongo::getReadConnection());
		
		Shanty_Mongo::removeConnectionGroups();
		
		$connection = $this->getMock('Shanty_Mongo_Connection');
		Shanty_Mongo::addSlave($connection, 1, 'users');
		$this->assertEquals($connection, Shanty_Mongo::getReadConnection('users'));
	}
	
	/**
	 * @depends testGetWriteConnection
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testNoReadConnections()
	{
		Shanty_Mongo::getReadConnection('users');
	}
	
	public function testAddConnectionsDefaultGroup()
	{
		$connections = array(
			'masters' => array(
				0 => array('host' => '127.0.0.1'),
				1 => array('host' => 'localhost')
			),
			'slaves' => array(
				0 => array('host' => '127.0.0.1'),
				1 => array('host' => 'localhost')
			)
		);
		
		Shanty_Mongo::addConnections($connections);
		$this->assertEquals(1, count(Shanty_Mongo::getConnectionGroups()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('default')->getMasters()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('default')->getSlaves()));
	}
	
	public function testAddConnectionsMultipleGroups()
	{
		$connections = array(
			'users' => array(
				'host' => 'localhost'
			),
			'accounts' => array(
				'masters' => array(
					0 => array('host' => '127.0.0.1'),
					1 => array('host' => 'localhost'),
				),
				'slaves' => array(
					0 => array('host' => '127.0.0.1'),
					1 => array('host' => 'localhost')
				)
			)
		);
		
		Shanty_Mongo::addConnections($connections);
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroups()));
		$this->assertEquals(1, count(Shanty_Mongo::getConnectionGroup('users')->getMasters()));
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroup('users')->getSlaves()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('accounts')->getMasters()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('accounts')->getSlaves()));
		
		Shanty_Mongo::removeConnectionGroups();
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroups()));
		
		Shanty_Mongo::addConnections(new Zend_Config($connections));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroups()));
		$this->assertEquals(1, count(Shanty_Mongo::getConnectionGroup('users')->getMasters()));
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroup('users')->getSlaves()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('accounts')->getMasters()));
		$this->assertEquals(2, count(Shanty_Mongo::getConnectionGroup('accounts')->getSlaves()));
	}
	
	public function testCreateRequirement()
	{
		$requirement = Shanty_Mongo::createRequirement('Validator:EmailAddress');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_EmailAddress', get_class($requirement));
		
		$requirement = Shanty_Mongo::createRequirement('Filter:Alpha');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Filter_Alpha', get_class($requirement));
		
		$requirement = Shanty_Mongo::createRequirement('Validator:InArray', array('one', 'two'));
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_InArray', get_class($requirement));
		$this->assertTrue($requirement->isValid('one'));
		$this->assertFalse($requirement->isValid('three'));
		
		// Make sure we get a fresh requirement with different options
		$requirement = Shanty_Mongo::createRequirement('Validator:InArray', array('three', 'four'));
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_InArray', get_class($requirement));
		$this->assertTrue($requirement->isValid('three'));
		$this->assertFalse($requirement->isValid('one'));
		
		$this->assertNull(Shanty_Mongo::createRequirement('Non existing requirement'));
	}
	
	/*
	 * @depends testCreateRequirement
	 */
	public function testRetrieveRequirements()
	{
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:MongoId');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Shanty_Mongo_Validate_Class', get_class($requirement));
		
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:Hostname');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_Hostname', get_class($requirement));
		$this->assertTrue($requirement->isValid('google.com'));
		
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:Hostname', Zend_Validate_Hostname::ALLOW_IP);
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_Hostname', get_class($requirement));
		$this->assertFalse($requirement->isValid('shantymongo.org'));

		$requirement = Shanty_Mongo::retrieveRequirement('Document:My_ShantyMongo_User');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Shanty_Mongo_Validate_Class', get_class($requirement));
		$user = $this->getMock('My_ShantyMongo_User');
		$this->assertTrue($requirement->isValid($user));
		
		$this->assertNull(Shanty_Mongo::createRequirement('Document:Class does not exist'));
		
		// even though we tested this with testCreateRequirement, we need to make sure the vars were passed through correctly
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:InArray', array('one', 'two'));
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_InArray', get_class($requirement));
		$this->assertTrue($requirement->isValid('one'));
		$this->assertFalse($requirement->isValid('three'));
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testRetrieveRequirementsException()
	{
		Shanty_Mongo::retrieveRequirement('Non existant requirement');
	}
	
	/**
	 * @depends testRetrieveRequirements
	 */
	public function testStoreRequirement()
	{
		$requirement = new Zend_Validate_Hostname();
		Shanty_Mongo::storeRequirement('Validator:Hostname', $requirement);
		
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:Hostname');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_Hostname', get_class($requirement));
		
		// test requirements with options after the same requirement has been stored without options
		$requirement = Shanty_Mongo::retrieveRequirement('Validator:Hostname', Zend_Validate_Hostname::ALLOW_IP);
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_Hostname', get_class($requirement));
		$this->assertFalse($requirement->isValid('shantymongo.org'));
	}

	public function testCustomValidator()
	{
		$requirement = Shanty_Mongo::createRequirement('Validator:Zend_Validate_EmailAddress');
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $requirement);
		$this->assertEquals('Zend_Validate_EmailAddress', get_class($requirement));
	}

	public static function validOperations()
	{
		return array(
			array('$set'), 
			array('$unset'), 
			array('$push'), 
			array('$pushAll'), 
			array('$pull'), 
			array('$pullAll'), 
			array('$inc')
		);
	}
	
	/**
	 * @dataProvider validOperations
	 */
	public function testValidOperation($operation)
	{
		$this->assertTrue(Shanty_Mongo::isValidOperation($operation));
		$this->assertFalse(Shanty_Mongo::isValidOperation('non valid op'));
	}
}