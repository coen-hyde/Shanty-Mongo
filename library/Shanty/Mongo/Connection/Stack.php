<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Connection_Stack implements SeekableIterator, Countable
{
	protected $_position = 0;
	protected $_nodes = array();
	protected $_weights = array();
	
	/**
	 * Add node to connection stack
	 * 
	 * @param Shanty_Mongo_Connection $connection
	 * @param int $weight
	 */
	public function addNode(Shanty_Mongo_Connection $connection, $weight = 1)
	{
		$this->_nodes[] = $connection;
		$this->_weights[] = (int) $weight;
	}
	
	/**
	 * Select a node from the connection stack. 
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public function selectNode()
	{
		$r = mt_rand(1,array_sum($this->_weights));
		$offset = 0;
		foreach ($this->_weights as $k => $weight) {
			$offset += $weight;
			if ($r <= $offset) {
				return $this->_nodes[$k];
			}
		}
	}
	
	public function seek($position)
	{
		$this->_position = $position;
		
		if (!$this->valid()) {
			throw new OutOfBoundsException("invalid seek position ($position)");
		}
	}
	
	public function current()
	{
		return $this->_nodes[$this->_position];
	}
	
	public function key()
	{
		return $this->_position;
	}
	
	public function next()
	{
		$this->_position +=1;
	}
	
	public function rewind()
	{
		$this->_position = 0;
	}
	
	public function valid()
	{
		return array_key_exists($this->_position, $this->_nodes);
	}
	
	public function count()
	{
		return count($this->_nodes);
	}
}