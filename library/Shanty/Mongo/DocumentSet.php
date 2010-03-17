<?php
/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @subpackage DocumentSet
 * @copyright  Shanty Tech Pty Ltd
 * @author     Coen Hyde
 */
class Shanty_Mongo_DocumentSet extends Shanty_Mongo_Document
{
	const DYNAMIC_INDEX = '$';
	
	/**
	 * Get a property
	 * 
	 * @param mixed $property
	 */
	public function getProperty($property = null)
	{
		// If property exists and initialised then return it
		if (!is_null($property) && array_key_exists($property, $this->_data)) {
			return $this->_data[$property];
		}
			
		// Fetch clean data for this property
		if (!is_null($property) && array_key_exists($property, $this->_cleanData)) $data = $this->_cleanData[$property];
		else $data = array();
		
		// If property is a reference to another document then fetch the reference document
		$reference = false;
		$collection = $this->getCollection();
		if (MongoDBRef::isRef($data)) {
			$collection = $data['$ref'];
			$data = MongoDBRef::get(static::getMongoDB(), $data);
			$reference = true;
		}
		
		
		$config = array ();
		$config['collection'] = $collection;
		$config['requirementModifiers'] = $this->getRequirements(self::DYNAMIC_INDEX.'.');
		$config['parentIsArray'] = true;
		$config['hasId'] = $this->hasRequirement(self::DYNAMIC_INDEX, 'hasId');
		
		if (!$reference) {
			// If this is a new array element. send the path to array. we will $push to the array when saving
			if (is_null($property)) $path = $this->getPathToDocument();
			else $path = $this->getPathToProperty($property);
			
			$config['pathToDocument'] = $path;
			$config['criteria'] = $this->getCriteria();
		}
		
		// get the document class
		if (!$className = $this->hasRequirement(self::DYNAMIC_INDEX, 'Document')) {
			$className = 'Shanty_Mongo_Document';
		}
		
		$document = new $className($data, $config);
		
		// Make sure object is a document
		if (!($document instanceof Shanty_Mongo_Document)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("{$className} is not a Shanty_Mongo_Document");
		}
		
		return $document;
	}
	
	/**
	 * Set property
	 * 
	 * @param $index
	 * @param $document
	 */
	public function setProperty($index, Shanty_Mongo_Document $document)
	{
		// Make sure index is numeric
		if (!is_null($index) && !is_numeric($index)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("Index must be numeric '{$index}' given");
		}
		
		// Throw exception if value is not valid
		$validators = $this->getValidators(self::DYNAMIC_INDEX);
		if (!$validators->isValid($document)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception(implode($validators->getMessages(), "\n"));
		}
		
		// Clone document
		$documentClone = clone $document;
		
		// Inform the document of it's surroundings
		$documentClone->setCollection($this->getCollection());
		$documentClone->setPathToDocument($this->getPathToDocument());
		$documentClone->setConfigAttribute('criteria', $this->getCriteria());
			
		// Filter value
		$value = $this->getFilters(self::DYNAMIC_INDEX)->filter($documentClone);
		
		if (is_null($index)) $this->_data[] = $documentClone;
		else $this->_data[$index] = $documentClone;
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
		$maxKey = max(array_keys($exportData));
		
		for ($i = 0; $i<$maxKey; $i++) {
			if (array_key_exists($i, $exportData)) continue;
			
			$exportData[$i] = null;
		}
		
		ksort($exportData);
		
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
	
	public function __call($name, $arguments = array())
	{
		switch ($name) {
			case 'new' :
				return $this->getProperty();
		}
		
		return call_user_func_array(array($this, $name), $arguments);
	}
}