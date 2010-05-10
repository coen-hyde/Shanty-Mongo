<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestSetup.php';

class Shanty_Mongo_Connection_StackTest extends Shanty_Mongo_TestSetup
{
	protected $_stack;
	
	public function setUp()
	{
		parent::setUp();
		
		$this->_stack = new Shanty_Mongo_Connection_Stack();
	}
	
	public function testOptionGetAndSet()
	{
		$this->assertTrue($this->_stack->getOption('cacheConnectionSelection'));
		$this->assertNull($this->_stack->getOption('option does not exist'));
		
		$this->_stack->setOption('cacheConnectionSelection', false);
		$this->assertFalse($this->_stack->getOption('cacheConnectionSelection'));
		
		$options = array(
			'cacheConnectionSelection' => true
		);
		
		$this->_stack->setOptions($options);
		$this->assertTrue($this->_stack->getOption('cacheConnectionSelection'));
	}
	
	public function testCacheConnectionSelection()
	{
		$this->assertTrue($this->_stack->cacheConnectionSelection());
		$this->_stack->cacheConnectionSelection(false);
		$this->assertFalse($this->_stack->cacheConnectionSelection());
	}
	
	public function testAddAndCountNodes()
	{
		$this->assertEquals(0, count($this->_stack));
		
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->assertEquals(1, count($this->_stack));
		
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->assertEquals(2, count($this->_stack));
	}
	
	public function testNoNodes()
	{
		$this->assertEquals(0, count($this->_stack));
		$this->assertNull($this->_stack->selectNode());
	}
	
	/**
	 * @depends testAddAndCountNodes
	 */
	public function testSelectNode()
	{
		$connection = $this->getMock('Shanty_Mongo_Connection');
		$this->_stack->addNode($connection);
		$this->assertEquals($connection, $this->_stack->selectNode());
	}
	
	/**
	 * @depends testCacheConnectionSelection
	 * @depends testAddAndCountNodes
	 * @depends testSelectNode
	 */
	public function testCacheConnection()
	{
		$this->_stack->cacheConnectionSelection(true);
		
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		
		$connection = $this->_stack->selectNode();
		for ($i = 0; $i < 100; $i++) {
			if ($this->_stack->selectNode() != $connection) {
				$this->fail('Connection was not cached property');
			}
		} 
	}
	
	/**
	 * @depends testCacheConnectionSelection
	 * @depends testAddAndCountNodes
	 * @depends testSelectNode
	 */
	public function testNodeWeight()
	{
		$this->_stack->cacheConnectionSelection(false);
		
		$nodes = array();
		$nodes[] = array('connection' => $this->getMock('Shanty_Mongo_Connection'), 'weight' => 100, 'selected' => 0);
		$nodes[] = array('connection' => $this->getMock('Shanty_Mongo_Connection'), 'weight' => 300, 'selected' => 0);
		$nodes[] = array('connection' => $this->getMock('Shanty_Mongo_Connection'), 'weight' => 600, 'selected' => 0);
		
		foreach ($nodes as $node) {
			$this->_stack->addNode($node['connection'], $node['weight']);
		}
		
		for ($i = 0; $i < 1000; $i++) {
			$selectedNode = $this->_stack->selectNode();
			
			foreach ($nodes as $key => $node) {
				if ($node['connection'] !== $selectedNode) continue;
				
				$nodes[$key]['selected'] += 1;
				break;
			}
		}
		
		foreach ($nodes as $node) {
			$this->assertThat(
				$node['selected'],
				$this->logicalAnd(
					$this->greaterThan($node['weight'] - 100),
					$this->lessThan($node['weight'] + 100)
				)
			);
		}
	}
	
	/**
	 * @depends testAddAndCountNodes
	 */
	public function testNodeIterate()
	{
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		
		$counter = 0;
		foreach ($this->_stack as $node) {
			$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $node);
			$counter += 1;
		}
		
		$this->assertEquals(4, $counter);
		
		$this->assertEquals(4, $this->_stack->key());
		$this->assertFalse($this->_stack->valid());
		
		$this->_stack->rewind();
		
		$this->assertEquals(0, $this->_stack->key());
		
		$this->_stack->seek(2);
		$this->assertEquals(2, $this->_stack->key());
		
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testSeekNonNumericException()
	{
		$this->_stack->seek('asdfasdf');
	}
	
	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testSeekOutOfBoundsException()
	{
		$this->_stack->seek(100);
	}
	
	public function testArrayAccess()
	{
		$this->assertFalse($this->_stack->offsetExists(100));
		
		$connection1 = $this->getMock('Shanty_Mongo_Connection');
		$connection2 = $this->getMock('Shanty_Mongo_Connection');
		$this->_stack[0] = $connection1;
		$this->_stack[1] = $connection2;
		
		$this->assertEquals(2, count($this->_stack));
		
		$this->assertEquals($connection1, $this->_stack[0]);
		$this->assertEquals($connection2, $this->_stack[1]);
		$this->assertNull($this->_stack[2]);
		
		unset($this->_stack[0]);
		$this->assertNull($this->_stack[0]);
		$this->assertEquals(1, count($this->_stack));
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testOffsetSetNonNumericException()
	{
		$this->_stack['asdfasdf'] = $this->getMock('Shanty_Mongo_Connection');
	}
}