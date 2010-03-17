<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @subpackage Collection
 * @copyright  Shanty Tech Pty Ltd
 * @author     Coen Hyde
 */
abstract class Shanty_Mongo_Collection
{
	protected static $_dbName = null;
	protected static $_collectionName = null;
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
	 * Get an instance of MongoDb
	 * 
	 * @return MongoDb
	 */
	protected static function getMongoDb()
	{
		if (is_null(static::getDbName())) {
			throw new Shanty_Mongo_Exception(get_called_class().'::$_dbName is null');
		}
		
		return Shanty_Mongo::getDefaultConnection()->selectDB(static::getDbName());
	}
	
	/**
	 * Get an instance of MongoCollection
	 * 
	 * @return MongoCollection
	 */
	protected static function getMongoCollection()
	{
		if (is_null(static::getCollectionName())) {
			throw new Shanty_Mongo_Exception(get_called_class().'::$_collectionName is null');
		}
		
		return static::getMongoDb()->selectCollection(static::getCollectionName());
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
		
		$data = static::getMongoCollection()->findOne($query);
		
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
		
		$cursor = static::getMongoCollection()->find($query);

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