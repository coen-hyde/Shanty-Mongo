<?php

require_once 'Shanty/Mongo/Document.php';

require_once 'Shanty/Mongo/Exception.php';
require_once 'Shanty/Mongo/Iterator/Cursor.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
abstract class Shanty_Mongo_Collection
{
	protected static $_connectionGroup = 'default';
	protected static $_db = null;
	protected static $_collection = null;
	protected static $_requirements = array();
	protected static $_cachedCollectionInheritance = array();
	protected static $_cachedCollectionRequirements = array();
	protected static $_documentSetClass = 'Shanty_Mongo_DocumentSet';
	
	/**
	 * Get the name of the mongo db
	 * 
	 * @return string
	 */
	public static function getDbName()
	{
		$db = static::$_db;

		if (is_null($db)) {
			$db = static::getConnection()->getDatabase();
		}

		return $db;
	}
	
	/**
	 * Get the name of the mongo collection
	 * 
	 * @return string
	 */
	public static function getCollectionName()
	{
		return static::$_collection;
	}
	
	/**
	 * Get the name of the connection group
	 * 
	 * @return string
	 */
	public static function getConnectionGroupName()
	{
		return static::$_connectionGroup;
	}

	/**
	 * Determine if this collection has a database name set
	 * 
	 * @return boolean
	 */
	public static function hasDbName()
	{
		return !is_null(static::getDbName());
	}
	
	/**
	 * Determine if this collection has a collection name set
	 * 
	 * @return boolean
	 */
	public static function hasCollectionName()
	{
		return !is_null(static::getCollectionName());
	}
	
	/**
	 * Is this class a document class
	 * 
	 * @return boolean
	 */
	public static function isDocumentClass()
	{
		return is_subclass_of(get_called_class(), 'Shanty_Mongo_Document');
	}
	
	/**
	 * Get the name of the document class
	 * 
	 * @return string
	 */
	public static function getDocumentClass()
	{
		if (!static::isDocumentClass()) {
			throw new Shanty_Mongo_Exception(get_called_class().' is not a document. Please extend Shanty_Mongo_Document');
		}
		
		return get_called_class();
	}
	
	/**
	 * Get the name of the document set class
	 * 
	 * @return string
	 */
	public static function getDocumentSetClass()
	{
		return static::$_documentSetClass;
	}
	
	/**
	 * Get the inheritance of this collection
	 */
	public static function getCollectionInheritance()
	{
		$calledClass = get_called_class();
		
		// Have we already computed this collections inheritance?
		if (array_key_exists($calledClass, static::$_cachedCollectionInheritance)) {
			return static::$_cachedCollectionInheritance[$calledClass];
		}
		
		$parentClass = get_parent_class($calledClass);
		
		if (is_null($parentClass::getCollectionName())) {
			$inheritance = array($calledClass);
		}
		else {
			$inheritance = $parentClass::getCollectionInheritance();
			array_unshift($inheritance, $calledClass);
		}
		
		static::$_cachedCollectionInheritance[$calledClass] = $inheritance;
		return $inheritance;
	}
	
	/**
	 * Get requirements
	 * 
	 * @param bolean $inherited Include inherited requirements
	 * @return array
	 */
	public static function getCollectionRequirements($inherited = true)
	{
		$calledClass = get_called_class();
		
		// Return if we only need direct requirements. ie no inherited requirements
		if (!$inherited || $calledClass === __CLASS__) {
			$reflector = new ReflectionProperty($calledClass, '_requirements');
			if ($reflector->getDeclaringClass()->getName() !== $calledClass) return array();
	
			return static::makeRequirementsTidy($calledClass::$_requirements);
		}
		
		// Have we already computed this collections requirements?
		if (array_key_exists($calledClass, self::$_cachedCollectionRequirements)) {
			return self::$_cachedCollectionRequirements[$calledClass];
		}
		
		// Get parent collections requirements
		$parentClass = get_parent_class($calledClass);
		$parentRequirements = $parentClass::getCollectionRequirements();
		
		// Merge those requirements with this collections requirements
		$requirements = static::mergeRequirements($parentRequirements, $calledClass::getCollectionRequirements(false));
		self::$_cachedCollectionRequirements[$calledClass] = $requirements;
		
		return $requirements;
	}
	
	/**
	 * Process requirements to make sure they are in the correct format
	 * 
	 * @param array $requirements
	 * @return array
	 */
	public static function makeRequirementsTidy(array $requirements) {
		foreach ($requirements as $property => $requirementList) {
			if (!is_array($requirementList)) {
				$requirements[$property] = array($requirementList);
			}
				
			$newRequirementList = array();
			foreach ($requirements[$property] as $key => $requirement) {
				if (is_numeric($key)) $newRequirementList[$requirement] = null;
				else $newRequirementList[$key] = $requirement;
			}
			
			$requirements[$property] = $newRequirementList;
		}
			
		return $requirements;
	}
	
	/**
	 * Merge a two sets of requirements together
	 * 
	 * @param array $requirements
	 * @return array
	 */
	public static function mergeRequirements($requirements1, $requirements2)
	{
		$requirements = $requirements1; 
		
		foreach ($requirements2 as $property => $requirementList) {
			if (!array_key_exists($property, $requirements)) {
				$requirements[$property] = $requirementList;
				continue;
			}
			
			foreach ($requirementList as $requirement => $options) {
				// Find out if this is a Document or DocumentSet requirement
				$matches = array();
				preg_match("/^(Document|DocumentSet)(?::[A-Za-z][\w\-]*)?$/", $requirement, $matches);
				
				if (empty($matches)) {
					$requirements[$property][$requirement] = $options;
					continue;
				}

				// If requirement exists in existing requirements then unset it and replace it with the new requirements
				foreach ($requirements[$property] as $innerRequirement => $innerOptions) {
					$innerMatches = array();
					
					preg_match("/^{$matches[1]}(:[A-Za-z][\w\-]*)?/", $innerRequirement, $innerMatches);
					
					if (empty($innerMatches)) {
						continue;
					}
					
					unset($requirements[$property][$innerRequirement]);
					$requirements[$property][$requirement] = $options;
					break;
				}
			}
		}
		
		return $requirements;
	}

	/*
	 * Get a connection
	 *
	 * @param $writable should the connection be writable
	 * @return Shanty_Mongo_Connection
	 */
	public static function getConnection($writable = true)
	{
		if ($writable) $connection = Shanty_Mongo::getWriteConnection(static::getConnectionGroupName());
		else $connection = Shanty_Mongo::getReadConnection(static::getConnectionGroupName());

		return $connection;
	}

	/**
	 * Get an instance of MongoDb
	 * 
	 * @return MongoDb
	 * @param boolean $useSlave
	 */
	public static function getMongoDb($writable = true)
	{
		if (!static::hasDbName()) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception(get_called_class().'::$_db is null');
		}

		return static::getConnection($writable)->selectDB(static::getDbName());
	}
	
	/**
	 * Get an instance of MongoCollection
	 * 
	 * @return MongoCollection
	 * @param boolean $useSlave
	 */
	public static function getMongoCollection($writable = true)
	{
		if (!static::hasCollectionName()) {
			throw new Shanty_Mongo_Exception(get_called_class().'::$_collection is null');
		}
		
		return static::getMongoDb($writable)->selectCollection(static::getCollectionName());
	}
	
	/**
	 * Create a new document belonging to this collection
	 * @param $data
	 * @param boolean $new
	 */
	public static function create(array $data = array(), $new = true)
	{
		if (isset($data['_type']) && is_array($data['_type']) && class_exists($data['_type'][0]) && is_subclass_of($data['_type'][0], 'Shanty_Mongo_Document')) {
			$documentClass = $data['_type'][0];
		}
		else {
			$documentClass = static::getDocumentClass();
		}
		
		$config = array();
		$config['new'] = ($new);
		$config['hasId'] = true;
		$config['connectionGroup'] = static::getConnectionGroupName();
		$config['db'] = static::getDbName();
		$config['collection'] = static::getCollectionName();
		return new $documentClass($data, $config);
	}
	
	/**
	 * Find a document by id
	 * 
	 * @param MongoId|String $id
	 * @param array $fields
	 * @return Shanty_Mongo_Document
	 */
	public static function find($id, array $fields = array())
	{
		if (!($id instanceof MongoId)) {
			$id = new MongoId($id);
		}
		
		$query = array('_id' => $id);
		
		return static::one($query, $fields);
	}
	
	/**
	 * Find one document
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return Shanty_Mongo_Document
	 */
	public static function one(array $query = array(), array $fields = array())
	{
		$inheritance = static::getCollectionInheritance();
		if (count($inheritance) > 1) {
			$query['_type'] = $inheritance[0];
		}

		// If we are selecting specific fields make sure _type is always there
		if (!empty($fields) && !isset($fields['_type'])) {
			$fields['_type'] = 1;
		}

		$data = static::getMongoCollection(false)->findOne($query, $fields);
		
		if (is_null($data)) return null;
		
		return static::create($data, false);
	}
	
	/** 
	 * Find many documents
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return Shanty_Mongo_Iterator_Cursor
	 */
	public static function all(array $query = array(), array $fields = array())
	{
		$inheritance = static::getCollectionInheritance();
		if (count($inheritance) > 1) {
			$query['_type'] = $inheritance[0];
		}

		// If we are selecting specific fields make sure _type is always there
		if (!empty($fields) && !isset($fields['_type'])) {
			$fields['_type'] = 1;
		}
		
		$cursor = static::getMongoCollection(false)->find($query, $fields);

		$config = array();
		$config['connectionGroup'] = static::getConnectionGroupName();
		$config['db'] = static::getDbName();
		$config['collection'] = static::getCollectionName();
		$config['documentClass'] = static::getDocumentClass();
		$config['documentSetClass'] = static::getDocumentSetClass();

		return new Shanty_Mongo_Iterator_Cursor($cursor, $config);
	}

	/**
	 * Alias for one
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return Shanty_Mongo_Document
	 */
	public static function fetchOne($query = array(), array $fields = array())
	{
		return static::one($query, $fields);
	}
	
	/**
	 * Alias for all
	 * 
	 * @param array $query
	 * @param array $fields
	 * @return Shanty_Mongo_Iterator_Cursor
	 */
	public static function fetchAll($query = array(), array $fields = array())
	{
		return static::all($query, $fields);
	}
	
	/**
	 * Select distinct values for a property
	 * 
	 * @param String $property
	 * @return array
	 */
	public static function distinct($property, $query = array())
	{
		$results = static::getMongoDb(false)->command(array('distinct' => static::getCollectionName(), 'key' => $property, 'query' => $query));
		
		return $results['values'];
	}
	
	/**
	 * Insert a document
	 * 
	 * @param array $document
	 * @param array $options
	 */
	public static function insert(array $document, array $options = array())
	{
		return static::getMongoCollection(true)->insert($document, $options);
	}

	/**
	 * Insert a batch of documents
	 *
	 * @param array $documents
	 * @param unknown_type $options
	 */
	public static function insertBatch(array $documents, array $options = array())
	{
		return static::getMongoCollection(true)->batchInsert($documents, $options);
	}
	
	/**
	 * Update documents from this collection
	 * 
	 * @param $criteria
	 * @param $object
	 * @param $options
	 */
	public static function update(array $criteria, array $object, array $options = array())
	{
		return static::getMongoCollection(true)->update($criteria, $object, $options);
	}
	
	/**
	 * Remove documents from this collection
	 * 
	 * @param array $criteria
	 * @param unknown_type $justone
	 */
	public static function remove(array $criteria, array $options = array())
	{
		// if you want to remove a document by MongoId
	        if (array_key_exists('_id', $criteria) && !($criteria["_id"] instanceof MongoId)) {
	            $criteria["_id"] = new MongoId($criteria["_id"]);
	        }
	        
		return static::getMongoCollection(true)->remove($criteria, $options);
	}
	
	/**
	 * Drop this collection
	 */
	public static function drop()
	{
		return static::getMongoCollection(true)->drop();
	}
	
	/**
	 * Ensure an index 
	 * 
	 * @param array $keys
	 * @param array $options
	 */
	public static function ensureIndex(array $keys, $options = array())
	{
		return static::getMongoCollection(true)->ensureIndex($keys, $options);
	}
	
	/**
	 * Delete an index
	 * 
	 * @param string|array $keys
	 */
	public static function deleteIndex($keys)
	{
		return static::getMongoCollection(true)->deleteIndex($keys);
	}
	
	/**
	 * Remove all indexes from this collection
	 */
	public static function deleteIndexes()
	{
		return static::getMongoCollection(true)->deleteIndexes();
	}
	
	/**
	 * Get index information for this collection
	 * 
	 * @return array
	 */
	public static function getIndexInfo()
	{
		return static::getMongoCollection(false)->getIndexInfo();
	}
}