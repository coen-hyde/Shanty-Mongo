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
     * Set to false if the query you are running doesnt reqire any type of setup on the _type variable
     *
     * @var bool
     */
	protected static $_requireType = true;

    /**
     * Enforce converting any _id field to a MongoId field
     *
     * @var bool
     */
    protected static $_enforceMongoIdType = true;

    /**
     * Field that is autmatically controlled via a select statement to make export() ignore requirements if we did any field limiting in our query
     *
     * @var bool
     */
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
     * Stores the last query
     *
     * @var array
     */
    protected static $_lastQuery = array();

    /**
     * Stores the last field query
     *
     * @var array
     */
    protected static $_lastFields = array();

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
	public static function getDocumentClass($useTest = true)
	{
		if (!static::isDocumentClass() && $useTest) {
			throw new Shanty_Mongo_Exception(get_called_class().' is not a document. Please extend Shanty_Mongo_Document');
		}
		
		return get_called_class();
	}

    /**
     * Is this parent class a document class
     *
     * @return boolean
     */
    public static function isParentDocumentClass()
    {
        return is_subclass_of(get_parent_class(static::getDocumentClass(false)), 'Shanty_Mongo_Document')
            || get_parent_class(static::getDocumentClass(false)) ==  'Shanty_Mongo_Document';
    }

    /**
     * Get the name of the document class
     *
     * @return string
     */
    public static function getParentDocumentClass($useTest = true)
    {
        if (!static::isParentDocumentClass() && $useTest) {
            throw new Shanty_Mongo_Exception(get_parent_class(static::getDocumentClass(false)).' is not a document. Please extend Shanty_Mongo_Document');
        }

        return get_parent_class(static::getDocumentClass(false));
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
		$calledClass = static::getDocumentClass(false);
		
		// Have we already computed this collections inheritance?
		if (array_key_exists($calledClass, static::$_cachedCollectionInheritance)) {
			return static::$_cachedCollectionInheritance[$calledClass];
		}

        /** @var $parentClass Shanty_Mongo_Document */
		$parentClass = static::getParentDocumentClass(false);
		
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
        /** @var $calledClass Shanty_Mongo_Document */
		$calledClass = static::getDocumentClass(false);
		
		// Return if we only need direct requirements. ie no inherited requirements
		if (!$inherited || $calledClass === __CLASS__) {
			$reflector = new ReflectionProperty($calledClass, '_requirements');
			if ($reflector->getDeclaringClass()->getName() !== $calledClass) return array();

			return static::makeRequirementsTidy($calledClass::$_requirements);
		}
		
		// Have we already computed this collections requirements? Then return from the cache
		if (isset(self::$_cachedCollectionRequirements[$calledClass])){
			return self::$_cachedCollectionRequirements[$calledClass];
		}
		
		/** @var $parentClass Shanty_Mongo_Collection */
		$parentClass = static::getParentDocumentClass(false);
		$parentRequirements = $parentClass::getCollectionRequirements();
		
		// Merge those requirements with this collections requirements
        self::$_cachedCollectionRequirements[$calledClass] = static::mergeRequirements(
            $parentRequirements,
            $calledClass::getCollectionRequirements(false)
        );
		
		return self::$_cachedCollectionRequirements[$calledClass];
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
		foreach ($requirements2 as $property => $requirementList) {
            // use isset rather than array_search for speed
			if (!isset($requirements1[$property])) {
				$requirements1[$property] = $requirementList;
				continue;
			}
			
			foreach ($requirementList as $requirement => $options) {
				// Find out if this is a Document or DocumentSet requirement
                if (!preg_match("/^(Document|DocumentSet)(?::[A-Za-z][\w\-]*)?$/", $requirement, $matches)) {
                    $requirements1[$property][$requirement] = $options;
                    continue;
                }

                // If requirement exists in existing requirements then unset it and replace it with the new requirements
                foreach (array_keys($requirements1[$property]) as $innerRequirement) {
                    if (!preg_match("/^{$matches[1]}(:[A-Za-z][\w\-]*)?/", $innerRequirement, $innerMatches)) {
                        continue;
                    }

                    //drop any other requirements that were set and over ride them with the doc requirements
                    unset($requirements1[$property][$innerRequirement]);

                    //set the primary doc requirement
                    $requirements1[$property][$requirement] = $options;

                    // since array_keys are unique, no need to continue once a match is found
                    break;
                }
            }
		}
		
		return $requirements1;
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
			throw new Shanty_Mongo_Exception(static::getDocumentClass().'::$_db is null');
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
		if (
            isset($data['_type'])
            && is_array($data['_type'])
            && class_exists($data['_type'][0])
            && is_subclass_of($data['_type'][0], 'Shanty_Mongo_Document')
        ) {
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
		$query = array('_id' => $id);

        /* run the query to the DB */
        return static::one($query, $fields);
	}

    /**
     * Make sure our fields for exclusion are being setup properly, we can not an inclusion and exclusion together so
     * we make sure we dont do an exclusion and force an inclusion on _type and _id and we never exclude _type and _id
     *
     * @static
     * @param array $fields
     * @return array
     */
    protected static function _fieldSetup(array $fields = array())
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

            /* make sure someone cant exclude the _type field */
            if(isset($fields['_id']) && $fields['_id'] != 1)
                unset($fields['_id']);
        }

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

        static::$_lastFields = $fields;

        return $fields;
    }

    /**
     * Setup the query in a uniform way across all and one.  Verify that _type is being set with the proper
     * inheritance, and _id is transformed to a MongoId if $_enforceMongoIdType is set to true
     *
     * @static
     * @param $query
     * @return array
     */
    protected static function _querySetup($query)
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

        if(static::$_enforceMongoIdType)
        {
            /* if we are dealing with a string, then convert it, otherwise someone already has a different intent */
            if(isset($query['_id']) && !is_array($query['_id']) && !$query['_id'] instanceof MongoId)
                $query['_id'] = new MongoId($query['_id']);
            else if(isset($query['_id']) && is_array($query['_id']))
                /* otherwise if we have an operator, take care of the sub arrays */
                foreach($query['_id'] as $operator => $ids)
                {
                    /* works on $in, $all, $nin, etc */
                    if(is_array($ids))
                        foreach($ids as $index => $id)
                            if(is_string($id) && !$id instanceof MongoId)
                                $ids[$index] = new MongoId($id);

                    /* works well with $ne operator */
                    elseif(is_string($ids) && !$ids instanceof MongoId)
                        $ids = new MongoId($ids);

                    $query['_id'][$operator] = $ids;
                }


        }

        static::$_lastQuery = $query;

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
		$cursor = static::getMongoCollection(false)->find($query)->fields($fields);

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
        /* run the query to the DB */
        return static::all($query, $fields);
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
        static::$_lastFields = array();

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

        /* make sure type is attached && only run this if we are enforcing a type for this document */
        if(static::$_requireType && !isset($document['_type']))
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
        /* only run this if we are enforcing a type for this document */
        if(static::$_requireType)
            /* make sure type is attached */
            foreach($documents as $key => $document)
                if(!isset($document['_type']))
                    $documents[$key]['_type'] = static::getCollectionInheritance(false);

        /* start the query */
        $profileKey = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'document' => $documents,
                'options' => $options,
            ),
            'insert'
        );

        /* run the query to the DB */
		$return = static::getMongoCollection(true)->batchInsert($documents, $options);

        /* end the query */
        Shanty_Mongo::getProfiler()->queryEnd($profileKey);

        return $return;
	}
	
	/**
	 * Update documents from this collection
	 * 
	 * @param $criteria
	 * @param $object
	 * @param $options
	 */
	public static function update(array $query, array $object, array $options = array())
	{
        $query = static::_querySetup($query);
        static::$_lastFields = array();

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'query' => $query,
                'object' => $object,
                'options' => $options,
            ),
            'update'
        );

        /* run the query to the DB */
		$return = static::getMongoCollection(true)->update($query, $object, $options);

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
	public static function remove(array $query, array $options = array())
	{
        $query = static::_querySetup($query);
        static::$_lastFields = array();

        /* start the query */
        $key = Shanty_Mongo::getProfiler()->startQuery(
            array(
                'database' => static::getDbName(),
                'collection' => static::getCollectionName(),
                'query' => $query,
                'options' => $options,
            ),
            'delete'
        );

        /* run the query to the DB */
        $return = static::getMongoCollection(true)->remove($query, $options);

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

    /**
     * Get the last query used to pull back records
     *
     * @static
     * @param bool $json return the query in JSON format
     * @return array|string
     */
    public static function getLastQuery($json = false)
    {
        if($json)
            return json_encode(static::$_lastQuery);
        else
            return static::$_lastQuery;
    }

    /**
     * Get the last fields used for limiting or requireing
     *
     * @static
     * @param bool $json return the query in JSON format
     * @return array|string
     */
    public static function getLastFields($json = false)
    {
        if($json)
            return json_encode(static::$_lastFields);
        else
            return static::$_lastFields;
    }

    /**
     * Get both fields and query in a combined array
     *
     * @static
     * @param $json
     * @return array|string
     */
    public static function getLastQueryCombined($json = false)
    {
        if($json)
            return json_encode(array('query' => static::$_lastQuery, 'fields' => static::$_lastFields));
        else
            return array('query' => static::$_lastQuery, 'fields' => static::$_lastFields);
    }
}