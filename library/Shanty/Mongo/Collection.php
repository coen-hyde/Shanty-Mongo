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
	protected static $_requirements = array(
		'_id' => 'Validator:MongoId'
	);
	
	protected static $_cachedCollectionRequirements = array();
	protected static $_documentSetClass = 'Shanty_Mongo_DocumentSet';
	
	/**
	 * Get the name of the mongo db
	 * 
	 * @return string
	 */
	public static function getDbName()
	{
		return static::$_db;
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
		if (array_key_exists($calledClass, static::$_cachedCollectionRequirements)) {
			return static::$_cachedCollectionRequirements[$calledClass];
		}
		
		// Get parent collections requirements
		$parentClass = get_parent_class($calledClass);
		$parentRequirements = $parentClass::getCollectionRequirements();
		
		// Merge those requirements with this collections requirements
		$requirements = static::mergeRequirements($parentRequirements, $calledClass::getCollectionRequirements(false));
		static::$_cachedCollectionRequirements[$calledClass] = $requirements;
		
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
		// Merge requirement modifiers with existing requirements
		return array_merge_recursive($requirements1, $requirements2);
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

		if ($writable) $connection = Shanty_Mongo::getWriteConnection(static::getConnectionGroupName());
		else $connection = Shanty_Mongo::getReadConnection(static::getConnectionGroupName());
		
		return $connection->selectDB(static::getDbName());
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
		$documentClass = static::getDocumentClass();
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
	 * @return Shanty_Mongo_Document
	 */
	public static function find($id)
	{
		if (!($id instanceof MongoId)) {
			$id = new MongoId($id);
		}
		
		$query = array('_id' => $id);
		
		return static::fetchOne($query);
	}
	
	/**
	 * Find one document
	 * 
	 * @param array $query
	 * @return Shanty_Mongo_Document
	 */
	public static function one(array $query = array())
	{
		$data = static::getMongoCollection(false)->findOne($query);
		
		if (is_null($data)) return null;
		
		return static::create($data, false);
	}
	
	/** 
	 * Find many documents
	 * 
	 * @param array $query
	 * @return Shanty_Mongo_Iterator_Cursor
	 */
	public static function all(array $query = array())
	{
		$cursor = static::getMongoCollection(false)->find($query);

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
	 * @return Shanty_Mongo_Document
	 */
	public static function fetchOne($query = array())
	{
		return static::one($query);
	}
	
	/**
	 * Alias for all
	 * 
	 * @param array $query
	 * @return Shanty_Mongo_Iterator_Cursor
	 */
	public static function fetchAll($query = array())
	{
		return static::all($query);
	}
	
	/**
	 * Insert a document
	 * 
	 * @param array $object
	 * @param unknown_type $safe
	 */
	public static function insert(array $object, $safe = false)
	{
		return static::getMongoCollection(true)->insert($object, $safe);
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
	public static function remove(array $criteria, $justone = false)
	{
		return static::getMongoCollection(true)->remove($criteria, $justone);
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