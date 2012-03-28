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
     * Container for holding server info that only needs to be queried for once
     *
     * @var array
     */
    protected static $_serverInfo = array();

    /**
     * Set to false if the query you are running doesnt reqire any type of setup on the _type variable
     *
     * @var bool
     */
	protected static $_requireType = true;
    protected static $_fieldLimiting = false;

	/**
     * Can be: direct - Top level inheiritance match | all - Match all levels of provided inheirtance | any - Match any inheiritance provided
     *
     * @var string
     */
    protected static $_inheritanceSearchType = 'direct';

    /**
     * Any extra inheiritance types can be added to this array
     *
     * @var array
     */
    protected static $_supplementalInheritance = array();

    /**
     * Automatically switch to any with supplemental inheritance types
     *
     * @var bool
     */
    protected static $_inheritanceSearchTypeAutoAny = true;

	/**
     * Get info about the read/write (master) server
     *
     * @static
     * @return array
     */
    public static function getWriteServerInfo()
    {
        return static::getMasterServerInfo();
    }

    /**
     * Get info about the read/write (master) server
     *
     * @static
     * @return array
     */
    public static function getMasterServerInfo()
    {
        return Shanty_Mongo::getWriteConnection(static::$_connectionGroup)->getServerInfo();
    }

    /**
     * Get info about the read only (slave) server
     *
     * @static
     * @return array
     */
    public static function getReadServerInfo()
    {
        return static::getSlaveServerInfo();
    }

    /**
     * Get info about the read only (slave) server
     *
     * @static
     * @return array
     */
    public static function getSlaveServerInfo()
    {
        return Shanty_Mongo::getReadConnection(static::$_connectionGroup)->getServerInfo();
    }

    /**
     * Combine master and slave information into one returnable server array
     *
     * @static
     * @return array
     */
    public static function getServerInfo()
    {
        return array(
            'master' => static::getMasterServerInfo(),
            'slave' => static::getSlaveServerInfo()
        );
    }

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
	public static function getCollectionInheritance($useSupplemental = true)
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
		
        /* useful when you refactor the class names in your code and you want _type to carry the old class name in your queries */
        if(is_array(static::$_supplementalInheritance) && count(static::$_supplementalInheritance) && $useSupplemental)
        {
            /* merge the supplementalInheritance to the inheritance array */
            $inheritance = array_merge($inheritance, static::$_supplementalInheritance);

            /* because of this, we are going to shift the match style to any */
            if(static::$_inheritanceSearchTypeAutoAny)
                static::$_inheritanceSearchType = 'any';
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
		if (isset($data['_type']) && is_array($data['_type']) && class_exists($data['_type'][0]) && is_subclass_of($data['_type'][0], 'Shanty_Mongo_Document')) {
			$documentClass = $data['_type'][0];
		}
		else {
			$documentClass = static::getDocumentClass();
		}
		
		$config = array();
        $config['fieldLimiting'] = static::$_fieldLimiting;
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

        /* run the query to the DB */
        $results = static::one($query, $fields);

		return $results;
	}

    private static function _fieldSetup(array $fields = array())
    {
        /* detect if we are doing an exclusionairy field set */
        $exclusion = false;
        foreach($fields as $key => $item)
            if($item < 1)
            {
                $exclusion = true;
                break;
            }

        /* if type is required and we are not dealing with an exclusion */
        if(static::$_requireType && !$exclusion)
        {
            // If we are selecting specific fields make sure _type is always there
            if (!empty($fields) && !isset($fields['_type'])) {
                $fields['_type'] = 1;
            }
        }
        else /* otherwise we just want to make sure type is not excluded */
        {
            /* make sure someone cant exclude the _type field */
            if(isset($fields['_type']) && $fields['_type'] != 1)
                unset($fields['_type']);
        }

        return $fields;
    }

    private static function _querySetup($query)
    {
        /* if type is a required field */
        if(static::$_requireType)
        {
            /* get the inheirtance from the collection */
            if (count($inheritance = static::getCollectionInheritance()) >= 1) {

                /* if we want to match against any inheritance type, then do a $in */
                if(static::$_inheritanceSearchType == 'any')
                    $query['_type'] = array('$in' => $inheritance);

                /* otherwise if we want to match against every inheirtance type, then do an all */
                else if(static::$_inheritanceSearchType == 'all')
                    $query['_type'] = array('$all' => $inheritance);

                /* otherwise just choose the top inheritance in the chain */
                else
                {
                    reset($inheritance);
                    $query['_type'] = current($inheritance);
                }

            }
        }

        return $query;
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
        $query = static::_querySetup($query);
        $fields = static::_fieldSetup($fields);

        if(
            /* make sure we have fields */
            count($fields)
            && (
                /* if we have more than 1 field, then we are not just looking for _type */
                count($fields) > 1
                || (
                    /* otherwise we need to make sure our 1 field is not _type */
                    count($fields) == 1
                    && !isset($fields['_type'])
                )
            )
        )
            static::$_fieldLimiting = true;

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'query' => $query,
                'fields' => $fields,
            )
        );

        /* run the query to the DB */
		$data = static::getMongoCollection(false)->findOne($query, $fields);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

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
        $query = static::_querySetup($query);
        $fields = static::_fieldSetup($fields);

        if(
            /* make sure we have fields */
            count($fields)
            && (
                /* if we have more than 1 field, then we are not just looking for _type */
                count($fields) > 1
                || (
                    /* otherwise we need to make sure our 1 field is not _type */
                    count($fields) == 1
                    && !isset($fields['_type'])
                )
            )
        )
            static::$_fieldLimiting = true;

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'query' => $query,
                'fields' => $fields,
            )
        );

        /* run the query to the DB */
		$cursor = static::getMongoCollection(false)->find($query, $fields);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);
        
		return new Shanty_Mongo_Iterator_Cursor($cursor, static::_createItteratorConfig());
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
        /* run the query to the DB */
        $results = static::one($query, $fields);

        return $results;
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

        /* run the query to the DB */
        $results = static::all($query, $fields);

		return $results;
	}
	
	/**
	 * Select distinct values for a property
	 * 
	 * @param String $property
	 * @return array
	 */
    public static function distinct($property, $query = null)
	{
        $query = static::_querySetup($query);

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'query' => $query,
                'property' => $property,
            )
        );

        /* run the query to the DB */
        $results = static::getMongoDb(false)->command(
            array(
                'distinct' => static::getCollectionName(),
                'key' => $property,
                'query' => $query
            )
        );

        /* end the query */
		Shanty_Mongo::getProfiler()->queryEnd($key);

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
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'document' => $document,
                'options' => $options,
            ),
            'insert'
        );

        /* make sure type is attached */
        if(!isset($document['_type']))
            $document['_type'] = static::getCollectionInheritance(false);

        /* run the query to the DB */
		$return = static::getMongoCollection(true)->insert($document, $options);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

        return $return;
	}

	/**
	 * Insert a batch of documents
	 *
	 * @param array $documents
	 * @param unknown_type $options
	 */
	public static function insertBatch(array $documents, array $options = array())
	{
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'document' => $documents,
                'options' => $options,
            ),
            'insert'
        );

        /* make sure type is attached */
        foreach($documents as $key => $document)
            if(!isset($document['_type']))
                $documents[$key]['_type'] = static::getCollectionInheritance(false);

        /* run the query to the DB */
		$return = static::getMongoCollection(true)->batchInsert($documents, $options);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

        return $return;
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
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'object' => $object,
                'options' => $options,
            ),
            'update'
        );

        /* run the query to the DB */
		$return = static::getMongoCollection(true)->update($criteria, $object, $options);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

        return $return;
	}
	
	/**
	 * Remove documents from this collection
	 * 
	 * @param array $criteria
	 * @param unknown_type $justone
	 */
	public static function remove(array $criteria, array $options = array())
	{
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'criteria' => $criteria,
                'options' => $options,
            ),
            'delete'
        );

        /* run the query to the DB */
        $return = static::getMongoCollection(true)->remove($criteria, $options);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

        return $return;
	}
	
	/**
	 * Drop this collection
	 */
	public static function drop()
	{
        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
            ),
            'delete'
        );

        /* run the query to the DB */
        $return = static::getMongoCollection(true)->drop();

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($key);

		return $return;
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

    private static function _createItteratorConfig()
    {
        $config = array();
        $config['fieldLimiting'] = static::$_fieldLimiting;
        $config['connectionGroup'] = static::getConnectionGroupName();
        $config['db'] = static::getDbName();
        $config['collection'] = static::getCollectionName();
        $config['documentClass'] = static::getDocumentClass();
        $config['documentSetClass'] = static::getDocumentSetClass();

        return $config;
    }
}