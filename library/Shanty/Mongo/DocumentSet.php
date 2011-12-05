<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_DocumentSet extends Shanty_Mongo_Document
{
	const DYNAMIC_INDEX = '$';
	
	protected static $_requirements = array(
		self::DYNAMIC_INDEX => 'Document'
	);
	
	/**
	 * Get the property keys for this Document Set
	 * 
	 * @return array
	 */
	public function getPropertyKeys()
	{
		$keys = parent::getPropertyKeys();
		sort($keys, SORT_NUMERIC);
		
		return $keys;
	}
	
	/**
	 * Get a property
	 * 
	 * @param mixed $property
	 */
	public function getProperty($index = null)
	{
		$new = is_null($index);
		
		// If property exists and initialised then return it
		if (!$new && array_key_exists($index, $this->_data)) {
			return $this->_data[$index];
		}
		
		// Make sure we are not trying to create a document that is supposed to be saved as a reference
		if ($new && $this->hasRequirement(static::DYNAMIC_INDEX, 'AsReference')) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("Can not create a new document from documentset where document must be saved as a reference");
		}

		if (!$new) {
			// Fetch clean data for this property if it exists
			if (array_key_exists($index, $this->_cleanData)) $data = $this->_cleanData[$index];
			else return null;
		}
		else $data = array();
		
		// If property is a reference to another document then fetch the reference document
		if (MongoDBRef::isRef($data)) {
			$collection = $data['$ref'];
			$data = MongoDBRef::get($this->_getMongoDB(false), $data);
			
			// If this is a broken reference then no point keeping it for later
			if (!$data) {
				$this->_data[$index] = null;
				return $this->_data[$index];
			}
			
			$reference = true;
		}
		else {
			$reference = false;
			$collection = $this->getConfigAttribute('collection');
		}
		
		$config = array ();
		$config['new'] = $new;
		$config['requirementModifiers'] = $this->getRequirements(self::DYNAMIC_INDEX.'.');
		$config['parentIsDocumentSet'] = true;
		$config['connectionGroup'] = $this->getConfigAttribute('connectionGroup');
		$config['db'] = $this->getConfigAttribute('db');
		$config['collection'] = $collection;
		
		if (!$reference) {
			// If this is a new array element. We will $push to the array when saving
			if ($new) $path = $this->getPathToDocument();
			else $path = $this->getPathToProperty($index);
			
			$config['pathToDocument'] = $path;
			$config['criteria'] = $this->getCriteria();
			$config['hasId'] = $this->hasRequirement(self::DYNAMIC_INDEX, 'hasId');
		}
		
		// get the document class
		$documentClass = $this->hasRequirement(self::DYNAMIC_INDEX, 'Document');
		if (isset($data['_type']) && !empty($data['_type'][0])) {
			$documentClass = $data['_type'][0];
		}
		$document = new $documentClass($data, $config);
		
		// if this document was a reference then remember that
		if ($reference) {
			$this->_references->attach($document);
		}
		
		// If this is not a new document cache it
		if (!$new) {
			$this->_data[$index] = $document;
		}
		
		return $document;
	}
	
	/**
	 * Set property
	 * 
	 * @param $index
	 * @param $document
	 */
	public function setProperty($index, $document)
	{
		$new = is_null($index);
		
		// Make sure index is numeric
		if (!$new && !is_numeric($index)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("Index must be numeric '{$index}' given");
		}
		
		// Unset element
		if (!$new && is_null($document)) {
			$this->_data[$index] = null;
			return;
		}
		
		// Make sure we are not keeping a copy of the old document in reference memory
		if (!$new && isset($this->_data[$index]) && !is_null($this->_data[$index])) {
			$this->_references->detach($this->_data[$index]);
		}
		
		// Throw exception if value is not valid
		$validators = $this->getValidators(self::DYNAMIC_INDEX);
		
		if (!$validators->isValid($document)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception(implode($validators->getMessages(), "\n"));
		}
		
		if ($new) {
			$keys = $this->getPropertyKeys();
			$index = empty($keys) ? 0 : max($keys)+1;
		}

		// Filter value
//		$value = $this->getFilters(self::DYNAMIC_INDEX)->filter($document);

		if (!$this->hasRequirement(self::DYNAMIC_INDEX, 'AsReference')) {
			// Make a new document if it has been saved somewhere else
			if (!$document->isNewDocument()) {
				$documentClass = get_class($document);
				$document = new $documentClass($document->export(), array('new' => false, 'pathToDocument' => $this->getPathToProperty($index)));
			}
			else {
				$document->setPathToDocument($this->getPathToProperty($index));
			}
			
			// Inform the document of it's surroundings
			$document->setConfigAttribute('connectionGroup', $this->getConfigAttribute('connectionGroup'));
			$document->setConfigAttribute('db', $this->getConfigAttribute('db'));
			$document->setConfigAttribute('collection', $this->getConfigAttribute('collection'));
			$document->setConfigAttribute('criteria', $this->getCriteria());
			$document->applyRequirements($this->getRequirements(self::DYNAMIC_INDEX.'.'));
		}
		
		$this->_data[$index] = $document;
	}
	
	/**
	 * Export all data
	 * 
	 * @return array
	 */
	public function export()
	{
		// Since this is an array, fill in empty index's with null
		$exportData = parent::export();
		
		// Fix PHP "max(): Array must contain at least one element" bug
        // if DocumentSet has no data
        if (count($exportData) > 0) {
    		$maxKey = max(array_keys($exportData));
    		
    		for ($i = 0; $i<$maxKey; $i++) {
    			if (array_key_exists($i, $exportData)) {
    				continue;
    			}
    			
    			$exportData[$i] = null;
    		}
    		
    		ksort($exportData);
        }
		
		return $exportData;
	}
	
	/**
	 * Add a document to this set
	 * 
	 * @param Shanty_Mongo_Document $document
	 */
	public function addDocument(Shanty_Mongo_Document $document)
	{
		return $this->setProperty(null, $document);
	}
	
	/**
	 * Add a document to the push queue
	 * 
	 * @param Shanty_Mongo_Document $document
	 */
	public function pushDocument(Shanty_Mongo_Document $document)
	{
		$this->push(null, $document);
	}
	
	/**
	 * Get all operations
	 * 
	 * @param Boolean $includingChildren Get operations from children as well
	 */
	public function getOperations($includingChildren = false)
	{
		if ($this->hasRequirement(self::DYNAMIC_INDEX, 'AsReference')) $includingChildren = false;
		
		return parent::getOperations($includingChildren);
	}
	
	/**
	 * Remove all operations
	 * 
	 * @param Boolean $includingChildren Remove operations from children as wells
	 */
	public function purgeOperations($includingChildren = false)
	{
		if ($this->hasRequirement(self::DYNAMIC_INDEX, 'AsReference')) $includingChildren = false;
		
		return parent::purgeOperations($includingChildren);
	}
	
	public function __call($name, $arguments = array())
	{
		switch ($name) {
			case 'new':
				return $this->getProperty();
		}
		
		require_once 'Shanty/Mongo/Exception.php';
		throw new Shanty_Mongo_Exception("Captured in __call. Method $name does not exist.");
	}
}