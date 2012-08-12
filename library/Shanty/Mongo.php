<?php

require_once 'Shanty/Mongo/Validate/Array.php';
require_once 'Shanty/Mongo/Validate/Class.php';
require_once 'Shanty/Mongo/Validate/StubTrue.php';
require_once 'Shanty/Mongo/Connection/Group.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo
{
	protected static $_connections = array();
	protected static $_requirements = array();
	protected static $_requirementCreators = array();
	protected static $_validOperations = array('$set', '$unset', '$push', '$pushAll', '$pull', '$pullAll', '$addToSet', '$pop', '$inc');
	protected static $_initialised = false;
	
	/**
	 * Initialise Shanty_Mongo. In particular all the requirements.
	 */
	public static function init()
	{
		// If requirements are not empty then we have already initialised requirements
		if (static::$_initialised) return;
		
		// Custom validators
		static::storeRequirement('Validator:Array', new Shanty_Mongo_Validate_Array());
		static::storeRequirement('Validator:MongoId', new Shanty_Mongo_Validate_Class('MongoId'));
		static::storeRequirement('Validator:MongoDate', new Shanty_Mongo_Validate_Class('MongoDate'));
		static::storeRequirement('Document', new Shanty_Mongo_Validate_Class('Shanty_Mongo_Document'));
		static::storeRequirement('DocumentSet', new Shanty_Mongo_Validate_Class('Shanty_Mongo_DocumentSet'));
		
		// Stubs
		static::storeRequirement('Required', new Shanty_Mongo_Validate_StubTrue());
		static::storeRequirement('AsReference', new Shanty_Mongo_Validate_StubTrue());
		
		// Requirement creator for validators
		static::storeRequirementCreator('/^Validator:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
			$instanceClass = 'Zend_Validate_'.$data[1];
			if (!class_exists($instanceClass)) return null;
			
			if (!is_null($options)) $validator = new $instanceClass($options);
			else $validator = new $instanceClass();
			
			if (!($validator instanceof Zend_Validate_Interface)) return null;
			
			return $validator;
		});
		
		// Requirement creator for filters
		static::storeRequirementCreator('/^Filter:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
			$instanceClass = 'Zend_Filter_'.$data[1];
			if (!class_exists($instanceClass)) return null;
			
			if (!is_null($options)) $validator = new $instanceClass($options);
			else $validator = new $instanceClass();
			
			if (!($validator instanceof Zend_Filter_Interface)) return null;
			
			return $validator;
		});
		
		// Creates requirements to match classes
		$classValidator = function($data) {
			if (!class_exists($data[1])) return null;
			
			return new Shanty_Mongo_Validate_Class($data[1]);
		};
		
		static::storeRequirementCreator('/^Document:([A-Za-z]+[\w\-]*)$/', $classValidator);
		static::storeRequirementCreator('/^DocumentSet:([A-Za-z]+[\w\-]*)$/', $classValidator);
		
		static::$_initialised = true;
	}
	
	/**
	 * Get the requirement matching the name provided
	 *
	 * @param $name String Name of requirement
	 * @return mixed
	 **/
	public static function retrieveRequirement($name, $options = null)
	{
		// Requirement is already initialised return it
		if (isset(static::$_requirements[$name])) {
			// If requirement does not have options, returned cached instance
			if (!$options)  return static::$_requirements[$name];
			
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
			static::storeRequirement($name, $requirement);
		}
		
		return $requirement;
	}
	
	/**
	 * Add requirements to use in validation of document properties
	 *
	 * @param $name String Name of requirement
	 * @param $requirement mixed
	 **/
	public static function storeRequirement($name, $requirement)
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
	public static function storeRequirementCreator($regex, Closure $function)
	{
		static::$_requirementCreators[$regex] = $function;
	}
	
	/**
	 * Create a requirement
	 *
	 * @param $name String Name of requirement
	 * @return mixed
	 **/
	public static function createRequirement($name, $options = null)
	{
		// Match requirement name against regex's
		$requirements = array_reverse(static::$_requirementCreators);
		foreach ($requirements as $regex => $function) {
			$matches = array();
			preg_match($regex, $name, $matches);
				
			if (!empty($matches)) {
				return $function($matches, $options);
			}
		}
		
		return null;
	}
	

	/**
	 * Remove all requirements
	 */
	public static function removeRequirements()
	{
		static::$_requirements = array();
	}
	
	/**
	 * Remove all requirement creators
	 */
	public static function removeRequirementCreators()
	{
		static::$_requirementCreators = array();
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
	 * Determine if a connection group exists
	 * 
	 * @param string $name The name of the connection group
	 */
	public static function hasConnection($name)
	{
		return array_key_exists($name, static::$_connections);
	}

	/**
	 * Set a connection group
	 * 
	 * @param string $name
	 * @param Shanty_Mongo_Connection_Group $connectionGroup
	 */
	public static function setConnection($name, Shanty_Mongo_Connection $connection)
	{
		static::$_connections[$name] = $connection;
	}
	
	/**
	 * Get a connection group. If it doesn't already exist, create it
	 * 
	 * @param string $name The name of the connection group
	 * @return Shanty_Mongo_Connection_Group
	 */
	public static function getConnection($name)
	{
		if (!static::hasConnection($name)) {
			return null;
		}
		
		return static::$_connections[$name];
	}
	
	/**
	 * Get a list of all connection groups
	 * 
	 * @return array
	 */
	public static function getConnections()
	{
		return static::$_connections;
	}
	
	/**
	 * Remove all connection groups
	 */
	public static function removeAllConnections()
	{
		static::$_connections = array();
	}
	
	
	public static function addConnection(Shanty_Mongo_Connection $connection, $connectionGroup = 'default')
	{
		static::setConnection($connectionGroup, $connection);
	}

	
	
	public static function getConnectionForGroup($connectionGroupName = 'default')
	{
		$connection = static::getConnection($connectionGroupName);
		
		return $connection;
	}

	/**
	 * Return Shanty_Mongo to pre-init status
	 */
	public static function makeClean()
	{
		static::removeConnections();
		static::removeRequirements();
		static::removeRequirementCreators();
		static::$_initialised = false;
	}
}

Shanty_Mongo::init();