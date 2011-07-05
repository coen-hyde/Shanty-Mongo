<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Iterator/Cursor.php';
 
class Shanty_Mongo_Iterator_CursorTest extends Shanty_Mongo_TestSetup
{
	public $_cursor = null;
	
	public function setUp()
	{
		parent::setUp();
		
		$config = array();
		$config['connectionGroup'] = 'connectionGroup';
		$config['db'] = TESTS_SHANTY_MONGO_DB;
		$config['collection'] = 'user';
		$config['documentClass'] = 'My_ShantyMongo_User';
		$config['documentSetClass'] = 'My_ShantyMongo_Users';
		$this->_cursor = new Shanty_Mongo_Iterator_Cursor($this->_connection->selectDb(TESTS_SHANTY_MONGO_DB)->selectCollection('user')->find(), $config);
	}
	
	public function testGetInnerIterator()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_cursor->getInnerIterator());
		$this->assertEquals('MongoCursor', get_class($this->_cursor->getInnerIterator()));
	}
	
	public function testGetDocumentClass()
	{
		$this->assertEquals('My_ShantyMongo_User', $this->_cursor->getDocumentClass());
	}
	
	public function testGetDocumentSetClass()
	{
		$this->assertEquals('My_ShantyMongo_Users', $this->_cursor->getDocumentSetClass());
	}
	
	public function testExport()
	{
		$exportData = array(
			'4c04516a1f5f5e21361e3ab0' => $this->_users['bob'],
			'4c04516f1f5f5e21361e3ab1' => $this->_users['cherry'],
			'4c0451791f5f5e21361e3ab2' => $this->_users['roger'],
		);
		
		$this->assertEquals($exportData, $this->_cursor->export());
	}
	
	public function testMakeDocumentSet()
	{
		$documentSet = $this->_cursor->makeDocumentSet();
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $documentSet);
		$this->assertEquals('My_ShantyMongo_Users', get_class($documentSet));
	}
	
	public function testIterate()
	{
		$names = array(
			'4c04516a1f5f5e21361e3ab0' => 'Bob Jones',
			'4c04516f1f5f5e21361e3ab1' => 'Cherry Jones',
			'4c0451791f5f5e21361e3ab2' => 'Roger Smith'
		);
		
		$namesCompare = array();
		
		foreach ($this->_cursor as $userId => $user) {
			switch ($userId) {
				case '4c04516a1f5f5e21361e3ab0':
					$this->assertEquals('My_ShantyMongo_Teacher', get_class($user));
					break;

				case '4c04516f1f5f5e21361e3ab1':
					$this->assertEquals('My_ShantyMongo_Student', get_class($user));
					break;

				case '4c0451791f5f5e21361e3ab2':
					$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($user));
					break;
			}

			$namesCompare[$userId] = $user->name->full();
		}
		
		$this->assertEquals($names, $namesCompare);
	}
	
	public function testCount()
	{
		$this->assertEquals(3, $this->_cursor->count());
	}
	
	public function testGetNext()
	{
		$this->assertEquals('Bob Jones', $this->_cursor->getNext()->name->full());
		$this->assertEquals('Cherry Jones', $this->_cursor->getNext()->name->full());
		$this->assertEquals('Roger Smith', $this->_cursor->getNext()->name->full());
		$this->assertEquals(null, $this->_cursor->getNext());
	}
	
	public function testInfo()
	{
		$info = $this->_cursor->info();
		$this->assertTrue(is_array($this->_cursor->info()));
	}
	
	/**
	 * @depends testInfo
	 */
	public function testMagicCall()
	{
		// Magic call returning Shanty_Mongo_Iterator_Cursor for chaining
		$cursor = $this->_cursor->limit(10)->sort(array('name.first'));
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cursor);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($cursor));
		
		$info = $this->_cursor->info();
		$this->assertEquals(10, $info['limit']);
		$this->assertEquals(array('name.first'), $info['query']['$orderby']);
		
		// Magic call returning boolean
		$this->assertTrue($cursor->hasNext());
	}
}