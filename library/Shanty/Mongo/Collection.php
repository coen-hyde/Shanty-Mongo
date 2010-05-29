<?php

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
	protected static $_dbName = null;
	protected static $_collectionName = null;
	protected static $_collectionRequirements = array();
	protected static $_cachedCollectionRequirements = null;
	protected static $_documentSetClass = 'Shanty_Mongo_DocumentSet';
	
	/**
	 * Get the name of the mongo db
	 * 
	 * @return string
	 */
	public static function getDbName()
	{
		return static::$_dbName;
	}
	
	/**
	 * Get the name of the mongo collection
	 * 
	 * @return string
	 */
	public static function getCollectionName()
	{
		return static::$_collectionName;
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
	 * @return array
	 */
	public static function getCollectionRequirements()
	{
		$calledClass = get_called_class();
		
		if (!isset($calledClass::$_collectionRequirements)) return array();

		return static::makeRequirementsTidy($calledClass::$_collectionRequirements);
	}
	
	/**
	 * Get all inherited requirements
	 * 
	 * @return array
	 */
	public static function getInheritedCollectionRequirements()
	{
		$calledClass = get_called_class();

		if ($calledClass === __CLASS__) return array();
		
		if (!is_null($calledClass::$_cachedCollectionRequirements)) {
			return $calledClass::$_cachedCollectionRequirements;
		}
		
		$parentClass = get_parent_class($calledClass);
		$parentRequirements = $parentClass::getInheritedCollectionRequirements();
		
		$requirements = static::mergeRequirements($parentRequirements, $calledClass::getCollectionRequirements());
		$calledClass::$_cachedCollectionRequirements = $requirements;
		
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
	protected static function getMongoDb($writable = true)
	{
		if (!static::hasDbName()) {
			throw new Shanty_Mongo_Exception(get_called_class().'::$_dbName is null');
		}

		if ($writable) $connection = Shanty_Mongo::getWriteConnection(static::$_connectionGroup);
		else $connection = Shanty_Mongo::getReadConnection(static::$_connectionGroup);
		
		return $connection->selectDB(static::getDbName());
	}
	
	/**
	 * Get an instance of MongoCollection
	 * 
	 * @return MongoCollection
	 * @param boolean $useSlave
	 */
	protected static function getMongoCollection($writable = true)
	{
		if (!static::hasCollectionName()) {
			throw new Shanty_Mongo_Exception(get_called_class().'::$_collectionName is null');
		}
		
		return static::getMongoDb($writable)->selectCollection(static::getCollectionName());
	}
	
	/**
	 * Create a new document belonging to this collection
	 * @param $data
	 */
	public static function create(array $data = array())
	{
		$documentClass = static::getDocumentClass();
		$config = array();
		$config['hasId'] = true;
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
	public static function fetchOne($query = array())
	{
		if (!is_array($query)) {
			throw new Shanty_Mongo_Exception("Query must ben an instance of Shanty_Mongo_Query or an array");
		}	
		
		$data = static::getMongoCollection(false)->findOne($query);
		
		if (is_null($data)) return null;
		
		return static::create($data);
	}
	
	/** 
	 * Find many documents
	 * 
	 * @param array $query
	 */
	public static function fetchAll($query = array())
	{
		if (!is_array($query)) {
			throw new Shanty_Mongo_Exception("Query must be an instance of Shanty_Mongo_Query or an array");
		}
		
		$cursor = static::getMongoCollection(false)->find($query);

		$config = array();
		$config['collection'] = static::getCollectionName();
		$config['documentClass'] = static::getDocumentClass();
		$config['documentSetClass'] = static::getDocumentSetClass();

		return new Shanty_Mongo_Iterator_Cursor($cursor, $config);
	}
	
	/**
	 * Insert a document
	 * 
	 * @param array $object
	 * @param unknown_type $safe
	 */
	public static function insert(array $object, $safe = false)
	{
		return static::getMongoCollection()->insert($object, $safe);
	}
	
	public static function update(array $criteria, array $object, array $options = array())
	{
		return static::getMongoCollection()->update($criteria, $object, $options);
	}
	
	public static function remove(array $criteria, $justone = false)
	{
		return static::getMongoCollection()->remove($criteria, $justone);
	}
	
	public static function drop()
	{
		return static::getMongoCollection()->drop();
	}
	
}