<?php

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo
{
	protected static $_defaultConnection = null;
	
	protected static $_masters = null;
	protected static $_slaves = null;
	
	protected static $_reselectConnectionEachRequest = false;
	protected static $_cachedWrite = null;
	protected static $_cachedRead = null;
	
	protected static $_requirements = array();
	protected static $_requirementCreators = array();
	protected static $_validOperations = array('$set', '$unset', '$push', '$pushAll', '$pull', '$pullAll', '$inc');
	
	/**
	 * Initialise Shanty_Mongo. In particular all the requirements.
	 */
	public static function init()
	{
		// If requirements are not empty then we have already initialised requirements
		if (!empty(static::$_requirements)) return;
		
		// Custom validators
		static::addRequirement('Validator:Array', new Shanty_Mongo_Validate_Array());
		static::addRequirement('Validator:MongoId', new Shanty_Mongo_Validate_Class('MongoId'));
		static::addRequirement('Document', new Shanty_Mongo_Validate_Class('Shanty_Mongo_Document'));
		static::addRequirement('DocumentSet', new Shanty_Mongo_Validate_Class('Shanty_Mongo_DocumentSet'));
		
		// Stubs
		static::addRequirement('Required', new Shanty_Mongo_Validate_StubTrue());
		static::addRequirement('AsReference', new Shanty_Mongo_Validate_StubTrue());
		
		// Requirement creator for validators
		static::addRequirementCreator('/^Validator:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
			$instanceClass = 'Zend_Validate_'.$data[1];
			if (!class_exists($instanceClass)) return null;
			
			$validator = new $instanceClass($options);
			if (!($validator instanceof Zend_Validate_Interface)) return null;
			
			return $validator;
		});
		
		// Requirement creator for filters
		static::addRequirementCreator('/^Filter:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
			$instanceClass = 'Zend_Filter_'.$data[1];
			if (!class_exists($instanceClass)) return null;
			
			$validator = new $instanceClass($options);
			if (!($validator instanceof Zend_Filter_Interface)) return null;
			
			return $validator;
		});
		
		// Creates requirements to match classes
		$classValidator = function($data) {
			if (!class_exists($data[1])) return null;
			
			return new Shanty_Mongo_Validate_Class($data[1]);
		};
		
		static::addRequirementCreator('/^Document:([A-Za-z]+[\w\-]*)$/', $classValidator);
		static::addRequirementCreator('/^DocumentSet:([A-Za-z]+[\w\-]*)$/', $classValidator);
	}
	
	/**
	 * Get the requirement matching the name provided
	 *
	 * @param $name String Name of requirement
	 * @return mixed
	 **/
	public static function getRequirement($name, $options = null)
	{
		// Requirement is already initialised return it
		if (array_key_exists($name, static::$_requirements)) {
			// If requirement does not have options, returned cached instance
			if (is_null($options))  return static::$_requirements[$name];
			
			$requirementClass = get_class(static::$_requirements[$name]);
			return new $requirementClass($options);
		}
		
		// Attempt to create requirement
		if (!$requirement = static::createRequirement($name, $options)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No requirement exists for '{$name}'");
		}
		
		// Requirement found. Store it for later use
		if (!is_null($options)) {
			static::addRequirement($name, $requirement);
		}
		
		return $requirement;
	}
	
	/**
	 * Add requirements to use in validation of document properties
	 *
	 * @param $name String Name of requirement
	 * @param $requirement mixed
	 **/
	public static function addRequirement($name, $requirement)
	{
		// Ensure $name is a string
		$name = (string) $name;
		
		static::$_requirements[$name] = $requirement;
	}
	
	/**
	 * Add a creator of requirements
	 *
	 * @param String Regex to match this requirement producer
	 * @param Closure Function to create requirement
	 **/
	public static function addRequirementCreator($regex, Closure $function)
	{
		static::$_requirementCreators[$regex] = $function;
	}
	
	/**
	 * Create a requirement
	 *
	 * @param $name String Name of requirement
	 * @return mixed
	 **/
	protected static function createRequirement($name, $options = null)
	{
		// Match requirement name against regex's
		foreach (static::$_requirementCreators as $regex => $function) {
			$matches = array();
			preg_match($regex, $name, $matches);
				
			if (!empty($matches)) {
				return $function($matches, $options);
			}
		}
		
		return null;
	}
	
	/**
	 * Deterimine if an operation is valid
	 * 
	 * @param string $operation
	 */
	public static function isValidOperation($operation)
	{
		return in_array($operation, static::$_validOperations);
	}
	
	/**
	 * Add a connection to a master server
	 * 
	 * @param Shanty_Mongo_Connection $connection
	 * @param int $weight
	 */
	public static function addMaster(Shanty_Mongo_Connection $connection, $weight = 1)
	{
		if (is_null(static::$_masters)) {
			static::$_masters = new Shanty_Mongo_Connection_Stack();
		}
		
		static::$_masters->addNode($connection, $weight);
	}
	
	/**
	 * Add a connection to a slaver server
	 * 
	 * @param $connection
	 * @param $weight
	 */
	public static function addSlave(Shanty_Mongo_Connection $connection, $weight = 1)
	{
		if (is_null(static::$_slaves)) {
			static::$_slaves = new Shanty_Mongo_Connection_Stack();
		}
		
		static::$_slaves->addNode($connection, $weight);
	}
	
	/**
	 * Get a write connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public static function getWriteConnection()
	{
		// If a connection is cached then return it
		if (!is_null(static::$_cachedWrite)) {
			return static::$_cachedWrite;
		}
		
		// If no master connections have been added assume localhost
		if (is_null(static::$_masters)) {
			static::addMaster(new Shanty_Mongo_Connection());
		}
		
		$write = static::$_masters->selectNode();
		
		// Should we remember this connection?
		if (!static::$_reselectConnectionEachRequest) {
			static::$_cachedWrite = $write;
		}
		
		$write->connect();
		
		return $write;
	}
	
	/**
	 * Get a read connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public static function getReadConnection()
	{
		// If a connection is cached then return it
		if (!is_null(static::$_cachedRead)) {
			return static::$_cachedRead;
		}
		
		if (is_null(static::$_slaves)) {
			$read = static::getWriteConnection();
		}
		else $read = static::$_slaves->selectNode();

		// Should we remember this connection?
		if (!static::$_reselectConnectionEachRequest) {
			static::$_cachedRead = $read;
		}
		
		$read->connect();
		
		return $read;
	}
	
}