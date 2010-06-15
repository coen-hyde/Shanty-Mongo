<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Iterator_Default implements SeekableIterator, RecursiveIterator, Countable
{
	protected $_document = null;
	protected $_position = null;
	protected $_properties = array();
	protected $_init = false;
	protected $_counter = 0;
	
	public function __construct(Shanty_Mongo_Document $document)
	{
		$this->_document = $document;
		$this->_properties = $document->getPropertyKeys();
		$this->_position = current($this->_properties);
		
		reset($this->_properties);
	}
	
	/**
	 * Get the document
	 * 
	 * @return Shanty_Mongo_Document
	 */
	public function getDocument()
	{
		return $this->_document;
	}
	
	/**
	 * Get the properties
	 * 
	 * @return array
	 */
	public function getDocumentProperties()
	{
		return $this->_properties;
	}
	
	/**
	 * Seek to a position
	 * 
	 * @param unknown_type $position
	 */
	public function seek($position)
	{
		$this->_position = $position;
		
		if (!$this->valid()) {
			throw new OutOfBoundsException("invalid seek position ($position)");
		}
	}
	
	/**
	 * Get the current value
	 * 
	 * @return mixed
	 */
	public function current()
	{
		return $this->getDocument()->getProperty($this->key());
	}
	
	/**
	 * Get the current key
	 * 
	 * @return string
	 */
	public function key()
	{
		return $this->_position;
	}
	
	/**
	 * Move next
	 */
	public function next()
	{
		next($this->_properties);
		$this->_position = current($this->_properties);
		$this->_counter = $this->_counter + 1;
	}
	
	/**
	 * Rewind the iterator
	 */
	public function rewind()
	{
		reset($this->_properties);
		$this->_position = current($this->_properties);
	}
	
	/**
	 * Is the current position valid
	 * 
	 * @return boolean
	 */
	public function valid()
	{
		return in_array($this->key(), $this->_properties, true);
	}

	/**
	 * Count all properties
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->getDocumentProperties());
	}
	
	/**
	 * Determine if the current node has an children
	 * 
	 * @return boolean
	 */
	public function hasChildren()
	{
		return ($this->current() instanceof Shanty_Mongo_Document);
	}
	
	/*
	 * Get children
	 * 
	 * @return RecursiveIterator
	 */
	public function getChildren()
	{
		return $this->current()->getIterator();
	}
}