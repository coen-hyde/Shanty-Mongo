<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Connection/Stack.php';
 
class Shanty_Mongo_Connection_StackTest extends PHPUnit_Framework_TestCase
{
	protected $_stack;
	
	public function setUp()
	{
		$this->_stack = new Shanty_Mongo_Connection_Stack();
	}
	
	public function testAddNode()
	{
		$this->assertEquals(0, count($this->_stack));
		
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->assertEquals(1, count($this->_stack));
		
		$this->_stack->addNode($this->getMock('Shanty_Mongo_Connection'));
		$this->assertEquals(2, count($this->_stack));
	}
	
	public function testSelectNode()
	{
		$connection = $this->getMock('Shanty_Mongo_Connection');
		$this->_stack->addNode($connection);
		$this->assertEquals($connection, $this->_stack->selectNode());
	}
	
	public function testNodeWeight()
	{
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
		
	}
}