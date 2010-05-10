<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Mongo' . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo.php';
require_once 'Zend/Config.php';
 
class Shanty_MongoTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		Shanty_Mongo::makeClean();
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
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, Shanty_Mongo::getConnectionGroups());
		$this->assertEquals(0, count(Shanty_Mongo::getConnectionGroups()));
		
		$connectionGroup = new Shanty_Mongo_Connection_Group();
		Shanty_Mongo::setConnectionGroup('users', $connectionGroup);
		$this->assertEquals(1, count(Shanty_Mongo::getConnectionGroups()));
		$this->assertEquals(Shanty_Mongo::getConnectionGroup('users'), $connectionGroup);
		
		$this->assertFalse(Shanty_Mongo::hasConnectionGroup('accounts'));
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, Shanty_Mongo::getConnectionGroup('accounts'));
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
		
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addMaster($connection);
		$this->assertEquals($connection, Shanty_Mongo::getWriteConnection());
		
		Shanty_Mongo::removeConnectionGroups();
		
		$connection = new Shanty_Mongo_Connection('localhost');
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
		
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addSlave($connection);
		$this->assertEquals($connection, Shanty_Mongo::getReadConnection());
		
		Shanty_Mongo::removeConnectionGroups();
		
		$connection = new Shanty_Mongo_Connection('localhost');
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
}