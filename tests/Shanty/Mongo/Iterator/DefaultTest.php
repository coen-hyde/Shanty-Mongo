<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Iterator/Default.php';
 
class Shanty_Mongo_Iterator_DefaultTest extends Shanty_Mongo_TestSetup
{
	protected $_iterator = null;
	
	public function setUp()
	{
		parent::setUp();
		
		$this->_document = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->_iterator = $this->_document->getIterator();
	}
	
	public function testGetDocument()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_iterator->getDocument());
		$this->assertEquals('My_ShantyMongo_User', get_class($this->_iterator->getDocument()));
	}
	
	public function testGetDocumentProperties()
	{
		$this->assertEquals($this->_document->getPropertyKeys(), $this->_iterator->getDocumentProperties());
	}
	
	public function testIteration()
	{
		$documentProperties = $this->_document->getPropertyKeys();
		$this->assertEquals($documentProperties[0], $this->_iterator->key());
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_iterator->current());
		$this->assertEquals('MongoId', get_class($this->_iterator->current()));
		$this->assertFalse($this->_iterator->hasChildren());
		
		$this->_iterator->next();
		$this->assertEquals($documentProperties[1], $this->_iterator->key());
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_iterator->current());
		$this->assertEquals('My_ShantyMongo_Name', get_class($this->_iterator->current()));
		
		$this->_iterator->seek('addresses');
		$this->assertEquals('addresses', $this->_iterator->key());
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_iterator->current());
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_iterator->current()));
		$this->assertTrue($this->_iterator->hasChildren());
		
		$addresses = $this->_iterator->getChildren();
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $addresses);
		$this->assertEquals('Shanty_Mongo_Iterator_Default', get_class($addresses));
		$this->assertEquals(2, count($addresses));
		
		$this->_iterator->rewind();
		$this->assertEquals($documentProperties[0], $this->_iterator->key());
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testSeekException()
	{
		$this->_iterator->seek('key does not exist');
	}
}