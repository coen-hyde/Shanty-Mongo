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
	protected static $_connectionGroups = array();
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
	 * Add connections Shanty Mongo
	 * 
	 * @param array $options
	 */
	public static function addConnections($options)
	{
		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		}
		
		$blurbs = array('host', 'master', 'masters', 'slaves', 'slave', 'hosts');
		$intersection = array_intersect(array_keys($options), $blurbs);

		$connectionGroups = array();
		if (!empty($intersection)) $connectionGroups['default'] = $options;
		else $connectionGroups = $options;
		
		foreach ($connectionGroups as $connectionGroupName => $connectionGroupOptions) {
			static::getConnectionGroup($connectionGroupName)->addConnections($connectionGroupOptions);
		}
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
	public static function hasConnectionGroup($name)
	{
		return array_key_exists($name, static::$_connectionGroups);
	}

	/**
	 * Set a connection group
	 * 
	 * @param string $name
	 * @param Shanty_Mongo_Connection_Group $connectionGroup
	 */
	public static function setConnectionGroup($name, Shanty_Mongo_Connection_Group $connectionGroup)
	{
		static::$_connectionGroups[$name] = $connectionGroup;
	}
	
	/**
	 * Get a connection group. If it doesn't already exist, create it
	 * 
	 * @param string $name The name of the connection group
	 * @return Shanty_Mongo_Connection_Group
	 */
	public static function getConnectionGroup($name)
	{
		if (!static::hasConnectionGroup($name)) {
			static::setConnectionGroup($name, new Shanty_Mongo_Connection_Group());
		}
		
		return static::$_connectionGroups[$name];
	}
	
	/**
	 * Get a list of all connection groups
	 * 
	 * @return array
	 */
	public static function getConnectionGroups()
	{
		return static::$_connectionGroups;
	}
	
	/**
	 * Remove all connection groups
	 */
	public static function removeConnectionGroups()
	{
		static::$_connectionGroups = array();
	}
	
	
	/**
	 * Add a connection to a master server
	 * 
	 * @param Shanty_Mongo_Connection $connection
	 * @param int $weight
	 */
	public static function addMaster(Shanty_Mongo_Connection $connection, $weight = 1, $connectionGroup = 'default')
	{
		static::getConnectionGroup($connectionGroup)->addMaster($connection, $weight);
	}
	
	/**
	 * Add a connection to a slaver server
	 * 
	 * @param $connection
	 * @param $weight
	 */
	public static function addSlave(Shanty_Mongo_Connection $connection, $weight = 1, $connectionGroup = 'default')
	{
		static::getConnectionGroup($connectionGroup)->addSlave($connection, $weight);
	}
	
	/**
	 * Get a write connection
	 * 
	 * @param string $connectionGroupName The connection group name
	 * @return Shanty_Mongo_Connection
	 */
	public static function getWriteConnection($connectionGroupName = 'default')
	{
		$connectionGroup = static::getConnectionGroup($connectionGroupName);
		
		if ($connectionGroupName == 'default' && count($connectionGroup->getMasters()) === 0) {
			// Add a connection to localhost if no connections currently exist for the default connection group
			$connectionGroup->addMaster(new Shanty_Mongo_Connection('127.0.0.1'));
		}
		
		if (!$connection = $connectionGroup->getWriteConnection($connectionGroupName)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No write connection available for the '{$connectionGroupName}' connection group");
		}
		
		return $connection;
	}
	
	/**
	 * Get a read connection
	 * 
	 * @param string $connectionGroupName The connection group name
	 * @return Shanty_Mongo_Connection
	 */
	public static function getReadConnection($connectionGroupName = 'default')
	{
		$connectionGroup = static::getConnectionGroup($connectionGroupName);
		
		if ($connectionGroupName == 'default' && count($connectionGroup->getSlaves()) === 0 && count($connectionGroup->getMasters()) === 0) {
			// Add a connection to localhost if no connections currently exist for the default connection group
			$connectionGroup->addMaster(new Shanty_Mongo_Connection('127.0.0.1'));
		}
		
		if (!$connection = $connectionGroup->getReadConnection($connectionGroupName)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No read connection available for the '{$connectionGroupName}' connection group");
		}
		
		return $connection;
	}
	
	/**
	 * Return Shanty_Mongo to pre-init status
	 */
	public static function makeClean()
	{
		static::removeConnectionGroups();
		static::removeRequirements();
		static::removeRequirementCreators();
		static::$_initialised = false;
	}
}

Shanty_Mongo::init();