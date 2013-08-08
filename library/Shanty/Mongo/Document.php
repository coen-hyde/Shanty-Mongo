<?php
require_once 'Shanty/Mongo/Exception.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/Iterator/Default.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License 
 */
class Shanty_Mongo_Document extends Shanty_Mongo_Collection implements ArrayAccess, Countable, IteratorAggregate
{
	protected static $_requirements = array(
		'_id' => 'Validator:MongoId',
		'_type' => 'Array'
	);
	
	protected $_docRequirements = array();
	protected $_filters = array();
	protected $_validators = array();
	protected $_data = array();
	protected $_cleanData = array();
	protected $_config = array(
		'new' => true,
		'connectionGroup' => null,
		'db' => null,
		'collection' => null,
		'pathToDocument' => null,
		'criteria' => array(),
		'parentIsDocumentSet' => false,
		'requirementModifiers' => array(),
		'locked' => false
	);
	protected $_operations = array();
	protected $_references = null;
	
	public function __construct($data = array(), $config = array())
	{
		// Make sure mongo is initialised
		Shanty_Mongo::init();
		
		$this->_config = array_merge($this->_config, $config);
		$this->_references = new SplObjectStorage();

		// If not connected and this is a new root document, figure out the db and collection
		if ($this->isNewDocument() && $this->isRootDocument() && !$this->isConnected()) {
			$this->setConfigAttribute('connectionGroup', static::getConnectionGroupName());
			$this->setConfigAttribute('db', static::getDbName());
			$this->setConfigAttribute('collection', static::getCollectionName());
		}
		
		// Get collection requirements
		$this->_docRequirements = static::getCollectionRequirements();
		
		// apply requirements requirement modifiers
		$this->applyRequirements($this->_config['requirementModifiers'], false);

		// Store data
		$this->_cleanData = $data;

		// Initialize input data
		if ($this->isNewDocument() && is_array($data)) {
			foreach ($data as $key => $value) {
				$this->getProperty($key);
			}
		}

		// Create document id if one is required
		if ($this->isNewDocument() && ($this->hasKey() || (isset($this->_config['hasId']) && $this->_config['hasId']))) {
			$this->_data['_id'] = new MongoId();
			$this->_data['_type'] = static::getCollectionInheritance();
		}
		
		// If has key then add it to the update criteria
		if ($this->hasKey()) {
			$this->setCriteria($this->getPathToProperty('_id'), $this->getId());
		}
		
		$this->init();
	}
	
	protected function init()
	{
		
	}
	
	protected function preInsert()
	{
		
	}
	
	protected function postInsert()
	{
		
	}
	
	protected function preUpdate()
	{
		
	}
	
	protected function postUpdate()
	{
		
	}
	
	protected function preSave()
	{
		
	}
	
	protected function postSave()
	{
		
	}
	
	protected function preDelete()
	{
		
	}
	
	protected function postDelete()
	{
		
	}
	
	/**
	 * Get this document's id
	 * 
	 * @return MongoId
	 */
	public function getId()
	{
		return $this->_id;
	}
	
	/**
	 * Set this document's id
	 * 
	 * @return MongoId
	 */
	public function setId(MongoId $id)
	{
		$this->_id = $id;
		$this->setConfigAttribute('new', false);
		$this->setCriteria($this->getPathToProperty('_id'), $id);
	}
	
	/**
	 * Does this document have an id
	 * 
	 * @return boolean
	 */
	public function hasId()
	{
		return !is_null($this->getId());
	}
	
	/**
	 * Get the inheritance of this document
	 * 
	 * @return array
	 */
	public function getInheritance()
	{
		return $this->_type;
	}
	
	/**
	 * Get a config attribute
	 * 
	 * @param string $attribute
	 */
	public function getConfigAttribute($attribute)
	{
		if (!$this->hasConfigAttribute($attribute)) return null;
		
		return $this->_config[$attribute];
	}
	
	/**
	 * Set a config attribute
	 * 
	 * @param string $attribute
	 * @param unknown_type $value
	 */
	public function setConfigAttribute($attribute, $value)
	{
		$this->_config[$attribute] = $value;
	}
	
	/**
	 * Determine if a config attribute is set
	 * 
	 * @param string $attribute
	 */
	public function hasConfigAttribute($attribute)
	{
		return array_key_exists($attribute, $this->_config);
	}
	
	/**
	 * Is this document connected to a db and collection
	 */
	public function isConnected()
	{
		return (!is_null($this->getConfigAttribute('connectionGroup')) && !is_null($this->getConfigAttribute('db')) && !is_null($this->getConfigAttribute('collection')));
	}
	
	/**
	 * Is this document locked
	 * 
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->getConfigAttribute('locked');
	}
	
	/**
	 * Get the path to this document from the root document
	 * 
	 * @return string
	 */
	public function getPathToDocument()
	{
		return $this->getConfigAttribute('pathToDocument');
	}
	
	/**
	 * Set the path to this document from the root document
	 * @param unknown_type $path
	 */
	public function setPathToDocument($path)
	{
		$this->setConfigAttribute('pathToDocument', $path);
	}
	
	/**
	 * Get the full path from the root document to a property
	 * 
	 * @param $property
	 * @return string
	 */
	public function getPathToProperty($property)
	{
		if ($this->isRootDocument()) return $property;
		
		return $this->getPathToDocument().'.'.$property;
	}

	/**
	 * Is this document a root document
	 * 
	 * @return boolean
	 */
	public function isRootDocument()
	{
		return is_null($this->getPathToDocument());
	}
	
/**
	 * Determine if this document has a key
	 * 
	 * @return boolean
	 */
	public function hasKey()
	{
		return ($this->isRootDocument() && $this->isConnected());
	}
	
	/**
	 * Is this document a child element of a document set
	 * 
	 * @return boolean
	 */
	public function isParentDocumentSet()
	{
		return $this->_config['parentIsDocumentSet'];
	}
	
	/**
	 * Determine if the document has certain criteria
	 * 
	 * @return boolean
	 */
	public function hasCriteria($property)
	{
		return array_key_exists($property, $this->_config['criteria']);
	}
	
	/**
	 * Add criteria
	 * 
	 * @param string $property
	 * @param MongoId $id
	 */
	public function setCriteria($property = null, $value = null)
	{
		$this->_config['criteria'][$property] = $value;
	}
	
	/**
	 * Get criteria
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function getCriteria($property = null)
	{
		if (is_null($property)) return $this->_config['criteria'];
		
		if (!array_key_exists($property, $this->_config['criteria'])) return null;
		
		return $this->_config['criteria'][$property];
	}
	
	/**
	 * Fetch an instance of MongoDb
	 * 
	 * @param boolean $writable
	 * @return MongoDb
	 */
	public function _getMongoDb($writable = true)
	{
		if (is_null($this->getConfigAttribute('db'))) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not fetch instance of MongoDb. Document is not connected to a db.');
		}
		
		if ($writable) $connection = Shanty_Mongo::getWriteConnection($this->getConfigAttribute('connectionGroup'));
		else $connection = Shanty_Mongo::getReadConnection($this->getConfigAttribute('connectionGroup'));
		
		$temp = $connection->selectDB($this->getConfigAttribute('db'));

		# Tells replica set how many nodes must have the data before success
//		$temp->w = 2;
		
		return $temp;
	}
	
	/**
	 * Fetch an instance of MongoCollection
	 * 
	 * @param boolean $writable
	 * @return MongoCollection
	 */
	public function _getMongoCollection($writable = true)
	{
		if (is_null($this->getConfigAttribute('collection'))) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not fetch instance of MongoCollection. Document is not connected to a collection.');
		}
		
		return $this->_getMongoDb($writable)->selectCollection($this->getConfigAttribute('collection'));
	}

	/**
	 * Apply a set of requirements
	 * 
	 * @param array $requirements
	 */
	public function applyRequirements($requirements, $dirty = true)
	{
		if ($dirty) {
			$requirements = static::makeRequirementsTidy($requirements);
		}
		
		$this->_docRequirements = static::mergeRequirements($this->_docRequirements, $requirements);
		$this->_filters = null;
		$this->_validators = null;
	}
	
	/**
	 * Test if this document has a particular requirement
	 * 
	 * @param string $property
	 * @param string $requirement
	 */
	public function hasRequirement($property, $requirement)
	{
		if (!array_key_exists($property, $this->_docRequirements)) return false;
		
		switch($requirement) {
			case 'Document':
			case 'DocumentSet':
				foreach ($this->_docRequirements[$property] as $requirementSearch => $params) {
					$standardClass = 'Shanty_Mongo_'.$requirement;
					
					// Return basic document or document set class if requirement matches
					if ($requirementSearch == $requirement) {
						return $standardClass;
					}
					
					// Find the document class
					$matches = array();
					preg_match("/^{$requirement}:([A-Za-z][\w\-]*)$/", $requirementSearch, $matches);
					
					if (!empty($matches)) {
						if (!class_exists($matches[1])) {
							require_once 'Shanty/Mongo/Exception.php';
							throw new Shanty_Mongo_Exception("$requirement class of '{$matches[1]}' does not exist");
						}
						
						if (!is_subclass_of($matches[1], $standardClass)) {
							require_once 'Shanty/Mongo/Exception.php';
							throw new Shanty_Mongo_Exception("$requirement of '{$matches[1]}' sub is not a class of $standardClass does not exist");
						}
						
						return $matches[1];
					}
				}
				
				return false;
		}
		
		return array_key_exists($requirement, $this->_docRequirements[$property]);
	}
	
	/**
	 * Get all requirements. If prefix is provided then only the requirements for 
	 * the properties that start with prefix will be returned.
	 * 
	 * @param string $prefix
	 */
	public function getRequirements($prefix = null)
	{
		// If no prefix is provided return all requirements
		if (is_null($prefix)) return $this->_docRequirements;
		
		// Find requirements for all properties starting with prefix
		$properties = array_filter(array_keys($this->_docRequirements), function($value) use ($prefix) {
			return (substr_compare($value, $prefix, 0, strlen($prefix)) == 0 && strlen($value) > strlen($prefix));
		});
		
		$requirements = array_intersect_key($this->_docRequirements, array_flip($properties));
		
		// Remove prefix from requirement key
		$newRequirements = array();
		array_walk($requirements, function($value, $key) use ($prefix, &$newRequirements) {
			$newRequirements[substr($key, strlen($prefix))] = $value;
		});
		
		return $newRequirements;
	}
	
	/**
	 * Add a requirement to a property
	 * 
	 * @param string $property
	 * @param string $requirement
	 */
	public function addRequirement($property, $requirement, $options = null)
	{
		if (!array_key_exists($property, $this->_docRequirements)) {
			$this->_docRequirements[$property] = array();
		}
		
		$this->_docRequirements[$property][$requirement] = $options;
		unset($this->_filters[$property]);
		unset($this->_validators[$property]);
	}
	
	/**
	 * Remove a requirement from a property
	 * 
	 * @param string $property
	 * @param string $requirement
	 */
	public function removeRequirement($property, $requirement)
	{
		if (!array_key_exists($property, $this->_docRequirements)) return;
		
		foreach ($this->_docRequirements[$property] as $requirementItem => $options) {
			if ($requirement === $requirementItem) {
				unset($this->_docRequirements[$property][$requirementItem]);
				unset($this->_filters[$property]);
				unset($this->_validators[$property]);
			}
		}
	}
	
	/**
	 * Get all the properties with a particular requirement
	 * 
	 * @param array $requirement
	 */
	public function getPropertiesWithRequirement($requirement)
	{
		$properties = array();
		
		foreach ($this->_docRequirements as $property => $requirementList) {
			if (strpos($property, '.') > 0) continue;
			
			if (array_key_exists($requirement, $requirementList)) {
				$properties[] = $property;
			}
		}
		
		return $properties;
	}

	/**
	 * Load the requirements as validators or filters for a given property,
	 * and cache them as validators or filters, respectively.
	 *
	 * @param String $property Name of property
	 * @return boolean whether or not cache was used. 
	 */
	public function loadRequirements($property)
	{
		if (isset($this->_validators[$property]) || isset($this->_filters[$property])) {
			return true;
		}

		$validators = new Zend_Validate;
		$filters = new Zend_Filter;

		if (!isset($this->_docRequirements[$property])) {
			$this->_filters[$property] = $filters;
			$this->_validators[$property] = $validators;
			return false;
		}

		foreach ($this->_docRequirements[$property] as $requirement => $options) {
			$req = Shanty_Mongo::retrieveRequirement($requirement, $options);
			if ($req instanceof Zend_Validate_Interface) {
				$validators->addValidator($req);
			} else if ($req instanceof Zend_Filter_Interface) {
				$filters->addFilter($req);
			}
		}
		$this->_filters[$property] = $filters;
		$this->_validators[$property] = $validators;
		return false;
	}
	
	/**
	 * Get all validators attached to a property
	 * 
	 * @param String $property Name of property
	 * @return Zend_Validate
	 **/
	public function getValidators($property)
	{
		$this->loadRequirements($property);
		return $this->_validators[$property];
	}
	
	/**
	 * Get all filters attached to a property
	 * 
	 * @param String $property
	 * @return Zend_Filter
	 */
	public function getFilters($property)
	{
		$this->loadRequirements($property);
		return $this->_filters[$property];
	}
	
	
	/**
	 * Test if a value is valid against a property
	 * 
	 * @param String $property
	 * @param Boolean $value
	 */
	public function isValid($property, $value)
	{
		$validators = $this->getValidators($property);
		
		return $validators->isValid($value);
	}
	
	/**
	 * Get a property
	 * 
	 * @param mixed $property
	 */
	public function getProperty($property)
	{
		// If property exists and initialised then return it
		if (array_key_exists($property, $this->_data)) {
			return $this->_data[$property];
		}

		// Fetch clean data for this property
		if (array_key_exists($property, $this->_cleanData)) {
			$data = $this->_cleanData[$property];
		}
		else {
			$data = array();
		}

		// If data is not an array then we can do nothing else with it
		if (!is_array($data)) {
			$this->_data[$property] = $data;
			return $this->_data[$property];
		}
	
		// If property is supposed to be an array then initialise an array
		if ($this->hasRequirement($property, 'Array')) {
			return $this->_data[$property] = $data;
		}
		
		// If property is a reference to another document then fetch the reference document
		$db = $this->getConfigAttribute('db');
		if (MongoDBRef::isRef($data)) {
			$collection = $data['$ref'];
			$data = MongoDBRef::get($this->_getMongoDB(false), $data);
			
			// If this is a broken reference then no point keeping it for later
			if (!$data) {
				$this->_data[$property] = null;
				return $this->_data[$property];
			}
			
			$reference = true;
		}
		else {
			$collection = $this->getConfigAttribute('collection');
			$reference = false;
		}
		
		// Find out the class name of the document or document set we are loaded
		if ($className = $this->hasRequirement($property, 'DocumentSet')) {
			$docType = 'Shanty_Mongo_DocumentSet';
		}
		else {
			$className = $this->hasRequirement($property, 'Document');
			
			// Load a document anyway so long as $data is not empty
			if (!$className && !empty($data)) {
				$className = 'Shanty_Mongo_Document';
			}
			
			if ($className) $docType = 'Shanty_Mongo_Document';
		}
		
		// Nothing else to do
		if (!$className) return null;
		
		// Configure property for document/documentSet usage
		$config = array();
		$config['new'] = empty($data);
		$config['connectionGroup'] = $this->getConfigAttribute('connectionGroup');
		$config['db'] = $this->getConfigAttribute('db');
		$config['collection'] = $collection;
		$config['requirementModifiers'] = $this->getRequirements($property.'.');
		$config['hasId'] = $this->hasRequirement($property, 'hasId');
		
		if (!$reference) {
			$config['pathToDocument'] = $this->getPathToProperty($property);
			$config['criteria'] = $this->getCriteria();
		}
		
		// Initialise document
		$document = new $className($data, $config);
		
		// if this document was a reference then remember that
		if ($reference) {
			$this->_references->attach($document);
		}
		
		$this->_data[$property] = $document;
		return $this->_data[$property];
	}
	
	/**
	 * Set a property
	 * 
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function setProperty($property, $value)
	{
		
		$validators = $this->getValidators($property);
		
		// Throw exception if value is not valid
		if (!is_null($value) && !$validators->isValid($value)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception(implode($validators->getMessages(), "\n"));
		}
		
		// Unset property
		if (is_null($value)) {
			$this->_data[$property] = null;
			return;
		}
		
		if ($value instanceof Shanty_Mongo_Document && !$this->hasRequirement($property, 'AsReference')) {
			if (!$value->isNewDocument() || !$value->isRootDocument()) {
				$documentClass = get_class($value);
				$value = new $documentClass($value->export(), array('new' => false, 'pathToDocument' => $this->getPathToProperty($property)));
			}
			else {
				$value->setPathToDocument($this->getPathToProperty($property));
			}
			
			$value->setConfigAttribute('connectionGroup', $this->getConfigAttribute('connectionGroup'));
			$value->setConfigAttribute('db', $this->getConfigAttribute('db'));
			$value->setConfigAttribute('collection', $this->getConfigAttribute('collection'));
			$value->setConfigAttribute('criteria', $this->getCriteria());
			$value->applyRequirements($this->getRequirements($property.'.'));
		}
		
		// Filter value
		$value = $this->getFilters($property)->filter($value);
		
		$this->_data[$property] = $value;
	}
	
	/**
	 * Determine if this document has a property
	 * 
	 * @param $property
	 * @return boolean
	 */
	public function hasProperty($property)
	{
		// If property has been initialised
		if (array_key_exists($property, $this->_data)) {
			return !is_null($this->_data[$property]);
		}
		
		// If property has not been initialised
		if (array_key_exists($property, $this->_cleanData)) {
			return !is_null($this->_cleanData[$property]);
		}
		
		return false;
	}

	/**
	 * Get a list of all property keys in this document
	 */
	public function getPropertyKeys()
	{
		$keyList = array();
		$doNoCount = array();
		
		foreach ($this->_data as $property => $value) {
			if (($value instanceof Shanty_Mongo_Document && !$value->isEmpty()) || 
				(!($value instanceof Shanty_Mongo_Document) && !is_null($value))) {
				$keyList[] = $property;
			}
			else {
				$doNoCount[] = $property;
			}
		}
		
		foreach ($this->_cleanData as $property => $value) {
			if (in_array($property, $keyList, true) || in_array($property, $doNoCount, true)) continue;
			
			if (!is_null($value)) $keyList[] = $property;
		}
		
		return $keyList;
	}
	
	/**
	 * Create a reference to this document
	 * 
	 * @return array
	 */
	public function createReference()
	{
		if (!$this->isRootDocument()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not create reference. Document is not a root document');
		}
		
		if (!$this->isConnected()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not create reference. Document does not connected to a db and collection');
		}
		
		return MongoDBRef::create($this->getConfigAttribute('collection'), $this->getId());
	}
	
	/**
	 * Test to see if a document is a reference in this document
	 * 
	 * @param Shanty_Mongo_Document $document
	 * @return boolean
	 */
	public function isReference(Shanty_Mongo_Document $document)
	{
		return $this->_references->contains($document);
	}
	
	/**
    * Determine if the document has a given reference or not
    *
    * @Return Boolean
    */
    public function hasReference($referenceName)
    {
        return !is_null($this->getProperty($referenceName));
    }
    
	/**
	 * Export all data
	 * 
	 * @return array
	 */
	public function export($skipRequired = false)
	{
		$exportData = $this->_cleanData;
		
		foreach ($this->_data as $property => $value) {
			// If property has been deleted
			if (is_null($value)) {
				unset($exportData[$property]);
				continue;
			}
			
			// If property is a document
			if ($value instanceof Shanty_Mongo_Document) {
				// Make when exporting from a documentset look up the correct requirement index
				if ($this instanceof Shanty_Mongo_DocumentSet) {
					$requirementIndex = Shanty_Mongo_DocumentSet::DYNAMIC_INDEX;
				}
				else {
					$requirementIndex = $property;
				}
				
				// If document is supposed to be a reference
				if ($this->hasRequirement($requirementIndex, 'AsReference') || $this->isReference($value)) {
					$exportData[$property] = $value->createReference();
					continue;
				}
				
				$data = $value->export();
				if (!empty($data)) {
					$exportData[$property] = $data;
				}
				continue;
			}
			
			$exportData[$property] = $value;
		}

		if (!$skipRequired) {

			// make sure required properties are not empty
			$requiredProperties = $this->getPropertiesWithRequirement('Required');
			foreach ($requiredProperties as $property) {
				if (!isset($exportData[$property]) || (is_array($exportData[$property]) && empty($exportData[$property]))) {
					require_once 'Shanty/Mongo/Exception.php';
					throw new Shanty_Mongo_Exception("Property '{$property}' must not be null.");
				}
			}

		}
				
		return $exportData;
	}
	
	/**
	 * Is this document a new document
	 * 
	 * @return boolean
	 */
	public function isNewDocument()
	{
		return ($this->_config['new']);
	}
	
	/**
	 * Test to see if this document is empty
	 * 
	 * @return Boolean
	 */
	public function isEmpty()
	{
		$doNoCount = array();
		
		foreach ($this->_data as $property => $value) {
			if ($value instanceof Shanty_Mongo_Document) {
				if (!$value->isEmpty()) return false;
			}
			elseif (!is_null($value)) {
				return false;
			}
			
			$doNoCount[] = $property;
		}
		
		foreach ($this->_cleanData as $property => $value) {
			if (in_array($property, $doNoCount)) {
				continue;
			}
			
			if (!is_null($value)) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Convert data changes into operations
	 * 
	 * @param array $data
	 */
	public function processChanges(array $data = array())
	{
		foreach ($data as $property => $value) {
			if ($property === '_id') continue;
			
			if (!array_key_exists($property, $this->_cleanData)) {
				$this->addOperation('$set', $property, $value);
				continue;
			}
			
			$newValue = $value;
			$oldValue = $this->_cleanData[$property];
			
			if (MongoDBRef::isRef($newValue) && MongoDBRef::isRef($oldValue)) {
				$newValue['$id'] = $newValue['$id']->__toString();
				$oldValue['$id'] = $oldValue['$id']->__toString();
			}
			
			if ($newValue !== $oldValue) {
				$this->addOperation('$set', $property, $value);
			}
		}
		
		foreach ($this->_cleanData as $property => $value) {
			if (array_key_exists($property, $data)) continue;
			
			$this->addOperation('$unset', $property, 1);
		}
	}
	
	/**
	 * Removes any properties that have been flagged as ignore in properties.
	 *
	 * @return void
	 * @author Tom Holder
	 **/
	public function removeIgnoredProperties(&$exportData)
	{
		// remove ignored properties
		$ignoreProperties = $this->getPropertiesWithRequirement('Ignore');
		
		foreach ($this->_data as $property => $document) {
			if (!($document instanceof Shanty_Mongo_Document)) continue;
			if ($this->isReference($document) || $this->hasRequirement($property, 'AsReference')) continue;

			$document->removeIgnoredProperties($exportData[$property]);
		}
			
		foreach ($ignoreProperties as $property) {
			unset($exportData[$property]);
		}
	}
	
	/**
	 * Save this document
	 * 
	 * @param boolean $entierDocument Force the saving of the entier document, instead of just the changes
	 * @param boolean $safe If FALSE, the program continues executing without waiting for a database response. If TRUE, the program will wait for the database response and throw a MongoCursorException if the update did not succeed
	 * @return boolean Result of save
	 */
	public function save($entierDocument = false, $safe = true)
	{
		if (!$this->isConnected()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not save documet. Document is not connected to a db and collection');
		}
		
		if ($this->isLocked()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not save documet. Document is locked.');
		}
		
		## execute pre hooks
		if ($this->isNewDocument()) $this->preInsert();
		else $this->preUpdate();
		
		$this->preSave();
		
		$exportData = $this->export();
		
		//Remove data with Ignore requirement.
		$this->removeIgnoredProperties($exportData);
		
		if ($this->isRootDocument() && ($this->isNewDocument() || $entierDocument)) {
			// Save the entier document
			$operations = $exportData;
		}
		else {
			// Update an existing document and only send the changes
			if (!$this->isRootDocument()) {
				// are we updating a child of an array?
				if ($this->isNewDocument() && $this->isParentDocumentSet()) {
					$this->_operations['$push'][$this->getPathToDocument()] = $exportData;
					$exportData = array();
					
					/**
					 * We need to lock this document because it has an incomplete document path and there is no way to find out it's true path.
					 * Locking prevents overriding the parent array on another save() after this save().
					 */
					$this->setConfigAttribute('locked', true);
				}
			}
			
			// Convert all data changes into sets and unsets
			$this->processChanges($exportData);
			
			$operations = $this->getOperations(true);

			// There are no changes, return so we don't blank the object
			if (empty($operations)) {
				return true;
			}
		}
		
		$result = false;
		
		if($this->isNewDocument())
		{
			$result = $this->_getMongoCollection(true)->update($this->getCriteria(), $operations, array('upsert' => true, 'safe' => $safe));
			$this->_cleanData = $exportData;
		}
		else
		{
			$newversion = $this->_getMongoDb(true)->command(
				array(
						'findandmodify' => $this->getConfigAttribute('collection'), 
						'query' => $this->getCriteria(), 
						'update'=>$operations,
						'new'=>true )
						);

			if(isset($newversion['value']))
				$this->_cleanData = $newversion['value'];

			if($newversion['ok'] == 1)
				$result = true;
		}

		$this->_data = array();
		$this->purgeOperations(true);
		
		// Run post hooks
		if ($this->isNewDocument()) {
			// This is not a new document anymore
			$this->setConfigAttribute('new', false);

			$this->postInsert();
		}
		else {
			$this->postUpdate();
		}
		
		$this->postSave();
		
		return $result;
	}
	
	/**
	 * Save this document without waiting for a response from the server
	 * 
	 * @param boolean $entierDocument Force the saving of the entier document, instead of just the changes
	 * @return boolean Result of save
	 */
	public function saveUnsafe($entierDocument = false)
	{
		return $this->save($entierDocument, false);
	}
	
	/**
	 * Delete this document
	 * 
	 * $return boolean Result of delete
	 */
	public function delete($safe = true)
	{
		if (!$this->isConnected()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not delete document. Document is not connected to a db and collection');
		}
	
		if ($this->isLocked()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception('Can not save documet. Document is locked.');
		}
		
		$mongoCollection = $this->_getMongoCollection(true);
		
		// Execute pre delete hook
		$this->preDelete();
		
		if (!$this->isRootDocument()) {
			$result = $mongoCollection->update($this->getCriteria(), array('$unset' => array($this->getPathToDocument() => 1)), array('safe' => $safe));
		}
		else {
			$result = $mongoCollection->remove($this->getCriteria(), array('justOne' => true, 'safe' => $safe));
		}
		
		// Execute post delete hook
		$this->postDelete();
		
		return $result;
	}
	
	/**
	 * Get a property
	 * 
	 * @param $property
	 * @return mixed
	 */
	public function __get($property)
	{
		return $this->getProperty($property);
	}
	
	/**
	 * Set a property
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value)
	{
		return $this->setProperty($property, $value);
	}
	
	/**
	 * Test to see if a property is set
	 * 
	 * @param string $property
	 */
	public function __isset($property)
	{
		return $this->hasProperty($property);
	}
	
	/**
	 * Unset a property
	 * 
	 * @param string $property
	 */
	public function __unset($property)
	{
		$this->_data[$property] = null;
	}
	
	/**
	 * Get an offset
	 * 
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getProperty($offset);
	}
	
	/**
	 * set an offset
	 * 
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		return $this->setProperty($offset, $value);
	}
	
	/**
	 * Test to see if an offset exists
	 * 
	 * @param string $offset
	 */
	public function offsetExists($offset)
	{
		return $this->hasProperty($offset);
	}
	
	/**
	 * Unset a property
	 * 
	 * @param string $offset
	 */
	public function offsetUnset($offset)
	{
		$this->_data[$offset] = null;
	}
	
	/**
	 * Count all properties in this document
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->getPropertyKeys());
	}
	
	/**
	 * Get the document iterator
	 * 
	 * @return Shanty_Mongo_DocumentIterator
	 */
	public function getIterator()
	{
		return new Shanty_Mongo_Iterator_Default($this);
	}
	
	/**
	 * Get all operations
	 * 
	 * @param Boolean $includingChildren Get operations from children as well
	 */
	public function getOperations($includingChildren = false)
	{
		$operations = $this->_operations;
		if ($includingChildren) {
			foreach ($this->_data as $property => $document) {
				if (!($document instanceof Shanty_Mongo_Document)) continue;
				
				if (!$this->isReference($document) && !$this->hasRequirement($property, 'AsReference')) {
					$operations = array_merge_recursive($operations, $document->getOperations(true));
				}
			}
		}
		
		return $operations;
	}
	
	/**
	 * Remove all operations
	 * 
	 * @param Boolean $includingChildren Remove operations from children as wells
	 */
	public function purgeOperations($includingChildren = false)
	{
		if ($includingChildren) {
			foreach ($this->_data as $property => $document) {
				if (!($document instanceof Shanty_Mongo_Document)) continue;
				
				if (!$this->isReference($document) || $this->hasRequirement($property, 'AsReference')) {
					$document->purgeOperations(true);
				}
			}
		}
		
		$this->_operations = array();
	}
	
	/**
	 * Add an operation
	 * 
	 * @param string $operation
	 * @param array $data
	 */
	public function addOperation($operation, $property = null, $value = null)
	{
		// Make sure the operation is valid
		if (!Shanty_Mongo::isValidOperation($operation)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("'{$operation}' is not valid operation");
		}
		
		// Prime the specific operation
		if (!array_key_exists($operation, $this->_operations)) {
			$this->_operations[$operation] = array();
		}
		
		// Save the operation
		if (is_null($property)) {
			$path = $this->getPathToDocument();
		}
		else {
			$path = $this->getPathToProperty($property);
		}
		
		// Mix operation with existing operations if needed
		switch($operation) {
			case '$pushAll':
			case '$pullAll':
				if (!array_key_exists($path, $this->_operations[$operation])) {
					break;
				}
				
				$value = array_merge($this->_operations[$operation][$path], $value);
				break;
		}
		
		$this->_operations[$operation][$path] = $value;
	}
	
	/**
	 * Increment a property by a specified amount
	 * 
	 * @param string $property
	 * @param int $value
	 */
	public function inc($property, $value = 1)
	{
		return $this->addOperation('$inc', $property, $value);
	}
	
	/**
	 * Push a value to a property
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function push($property = null, $value = null)
	{
		// Export value if needed
		if ($value instanceof Shanty_Mongo_Document) {
			$value = $value->export();
		}
		
		return $this->addOperation('$pushAll', $property, array($value));
	}
	
	/**
	 * Pull all occurrences a value from an array
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function pull($property, $value)
	{
		return $this->addOperation('$pullAll', $property, $value);
	}
	
	/*
	 * Adds value to the array only if its not in the array already.
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function addToSet($property, $value)
	{
		return $this->addOperation('$addToSet', $property, $value);
	}
	
	/*
	 * Removes an element from an array
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function pop($property, $value)
	{
		return $this->addOperation('$pop', $property, $value);
	}
}