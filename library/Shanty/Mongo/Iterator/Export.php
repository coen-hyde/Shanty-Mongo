<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Iterator_Export implements OuterIterator
{
	protected $_innerIterator = null;
	
	public function __construct(Iterator $iterator)
	{
		$this->_innerIterator = $iterator;
	}
	
	public function getInnerIterator()
	{
		return $this->_innerIterator;
	}
	
	/**
	 * Get the current value
	 * 
	 * @return mixed
	 */
	public function current()
	{
		$current = $this->getInnerIterator()->current();
		$document = $this->getInnerIterator()->getDocument();
		
		if ($current instanceof Shanty_Mongo_Document) {
			if ($document instanceof Shanty_Mongo_DocumentSet) $requirementKey = Shanty_Mongo_DocumentSet::DYNAMIC_INDEX;
			else $requirementKey = $this->key();
			
			if ($document->hasRequirement($requirementKey, 'AsReference') || $document->isReference($current)) {
				return $current->createReference();
			}
			else {
				$export = $current->export();
				if (!empty($export)) return $export;
				else return null;
			}
		}
		elseif (!is_null($current)) {
			return $current;
		}
		
		return null;
	}
	
	public function key()
	{
		return $this->getInnerIterator()->key();
	}
	
	public function next()
	{
		return $this->getInnerIterator()->next();
	}
	
	public function rewind()
	{
		return $this->getInnerIterator()->rewind();
	}
	
	public function valid()
	{
		return $this->getInnerIterator()->valid();
	}
}