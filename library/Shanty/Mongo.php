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
	protected static $_requirements = array();
	protected static $_requirementCreators = array();
	protected static $_validOperations = array('$set', '$unset', '$push', '$pushAll', '$pull', '$pullAll', '$inc');
	
	/**
	 * Initialise Shanty_Mongo. In particular all the requirements.
	 */
	public static function init()
	{
		// If requirements are not empty then we have already initialised requirements
		if (!empty(self::$_requirements)) return;
		
		// Custom validators
		self::addRequirement('Validator:Array', new Shanty_Mongo_Validate_Array());
		self::addRequirement('Validator:MongoId', new Shanty_Mongo_Validate_Class('MongoId'));
		self::addRequirement('Document', new Shanty_Mongo_Validate_Class('Shanty_Mongo_Document'));
		self::addRequirement('DocumentSet', new Shanty_Mongo_Validate_Class('Shanty_Mongo_DocumentSet'));
		
		// Stubs
		self::addRequirement('Required', new Shanty_Mongo_Validate_StubTrue());
		self::addRequirement('AsReference', new Shanty_Mongo_Validate_StubTrue());
		
		// Requirement creator for validators and filters
		self::addRequirementCreator('/^Validator:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
			$instanceClass = 'Zend_Validate_'.$data[1];
			if (!class_exists($instanceClass)) return null;
			
			$validator = new $instanceClass($options);
			if (!($validator instanceof Zend_Validate_Interface)) return null;
			
			return $validator;
		});
		
		// Requirement creator for filters
		self::addRequirementCreator('/^Filter:([A-Za-z]+[\w\-:]*)$/', function($data, $options = null) {
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
		
		self::addRequirementCreator('/^Document:([A-Za-z]+[\w\-]*)$/', $classValidator);
		self::addRequirementCreator('/^DocumentSet:([A-Za-z]+[\w\-]*)$/', $classValidator);
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
		if (array_key_exists($name, self::$_requirements)) {
			// If requirement does not have options, returned cached instance
			if (is_null($options))  return self::$_requirements[$name];
			
			$requirementClass = get_class(self::$_requirements[$name]);
			return new $requirementClass($options);
		}
		
		// Attempt to create requirement
		if (!$requirement = self::createRequirement($name, $options)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No requirement exists for '{$name}'");
		}
		
		// Requirement found. Store it for later use
		if (!is_null($options)) {
			self::addRequirement($name, $requirement);
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
		
		self::$_requirements[$name] = $requirement;
	}
	
	/**
	 * Add a creator of requirements
	 *
	 * @param String Regex to match this requirement producer
	 * @param Closure Function to create requirement
	 **/
	public static function addRequirementCreator($regex, Closure $function)
	{
		self::$_requirementCreators[$regex] = $function;
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
		foreach (self::$_requirementCreators as $regex => $function) {
			$matches = array();
			preg_match($regex, $name, $matches);
				
			if (!empty($matches)) {
				return $function($matches, $options);
			}
		}
		
		return null;
	}
	
	/**
	 * Set the default connection
	 * 
	 * @param Shanty_Mongo_Connection $connection
	 */
	public static function setDefaultConnection(Shanty_Mongo_Connection $connection)
	{
		self::$_defaultConnection = $connection;
	}
	
	/**
	 * Return the default connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public static function getDefaultConnection()
	{
		return self::$_defaultConnection;
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
}