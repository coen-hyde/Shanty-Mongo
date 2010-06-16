<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/DocumentSet.php';
 
class Shanty_Mongo_DocumentSetTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		$this->_bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->_article = My_ShantyMongo_Article::find('4c04516f1f5f5e21361e3ac1');
	}
	
	public function testGetProperty()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->addresses);
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_bob->addresses));
		$this->assertEquals(2, count($this->_bob->addresses));
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->addresses[0]);
		$this->assertEquals('Shanty_Mongo_Document', get_class($this->_bob->addresses[0]));
		
		$this->assertEquals('19 Hill St', $this->_bob->addresses[0]->street);
		
		$this->assertEquals('default', $this->_bob->addresses[0]->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->addresses[0]->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->addresses[0]->getConfigAttribute('collection'));
		$this->assertEquals('addresses.0', $this->_bob->addresses[0]->getPathToDocument());
		
		$criteria = $this->_bob->addresses[0]->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $criteria['_id']->__toString());
		
		$this->assertFalse($this->_bob->addresses[0]->isNewDocument());
		$this->assertTrue($this->_bob->addresses[0]->getConfigAttribute('parentIsDocumentSet'));
		
		$this->assertNull($this->_bob->addresses[404]);
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_article->contributors);
		$this->assertEquals('My_ShantyMongo_Users', get_class($this->_article->contributors));
		$this->assertEquals(2, count($this->_article->contributors));
		
		$user = $this->_article->contributors[0];
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $user);
		$this->assertEquals('My_ShantyMongo_User', get_class($user));
		$this->assertEquals('Cherry Jones', $user->name->full());
		
		$this->assertEquals('default', $user->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $user->getConfigAttribute('db'));
		$this->assertEquals('user', $user->getConfigAttribute('collection'));
		$this->assertEquals('', $user->getPathToDocument());
		
		$criteria = $user->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $criteria['_id']->__toString());
		
		// Test broken reference
		$this->assertNull($this->_bob->friends[2]);
	}
	
	public function testGetPropertyNewDocument()
	{
		$address = $this->_bob->addresses->new();
		$address->street = '45 Burrow St';
		$address->suburb = 'The Shire';
		$address->state = 'Middle Earth';
		$address->postcode = '21342';
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $address);
		$this->assertEquals('Shanty_Mongo_Document', get_class($address));
		$this->assertTrue($address->isNewDocument());
		$this->assertFalse($address->hasId());
		$this->assertTrue($address->getConfigAttribute('parentIsDocumentSet'));
		
		$this->assertEquals('default', $address->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $address->getConfigAttribute('db'));
		$this->assertEquals('user', $address->getConfigAttribute('collection'));
		$this->assertEquals('addresses', $address->getPathToDocument());
		
		$criteria = $address->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $criteria['_id']->__toString());
		
		$address->save();
		
		$bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->assertEquals(3, count($bob->addresses));
		$this->assertEquals('45 Burrow St', $bob->addresses[2]->street);
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetPropertyNewDocumentException()
	{
		$user = $this->_article->contributors->new();
	}
	
	public function testSetProperty()
	{
		// Unset and existing document
		$this->_bob->addresses[0] = null;
		$this->assertEquals(null, $this->_bob->addresses[0]);
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyNonNumericIndexException()
	{
		$this->_bob->addresses['non numeric index'] = new Shanty_Mongo_Document();
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyNonDocumentException()
	{
		$this->_bob->addresses[5] = 'Not a document';
	}
	
}