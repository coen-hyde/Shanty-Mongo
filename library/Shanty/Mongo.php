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
		
		// Validator requirements
		self::addRequirement('Alnum', new Zend_Validate_Alnum());
		self::addRequirement('Alpha', new Zend_Validate_Alpha());
		self::addRequirement('Array', new Shanty_Mongo_Validate_Array());
		self::addRequirement('CreditCard', new Zend_Validate_CreditCard());
		self::addRequirement('Digits', new Zend_Validate_Digits());
		self::addRequirement('Document', new Shanty_Mongo_Validate_Class('Shanty_Mongo_Document'));
		self::addRequirement('DocumentSet', new Shanty_Mongo_Validate_Class('Shanty_Mongo_DocumentSet'));
		self::addRequirement('EmailAddress', new Zend_Validate_EmailAddress());
		self::addRequirement('Float', new Zend_Validate_Float());
		self::addRequirement('Hex', new Zend_Validate_Hex());
		self::addRequirement('Hostname', new Zend_Validate_Hostname());
		self::addRequirement('Int', new Zend_Validate_Int());
		self::addRequirement('Ip', new Zend_Validate_Ip());
		self::addRequirement('NotEmpty', new Zend_Validate_NotEmpty());
		self::addRequirement('MongoId', new Shanty_Mongo_Validate_Class('MongoId'));
		
		// Filter requirements
		self::addRequirement('AsAlnum', new Zend_Filter_Alnum());
		self::addRequirement('AsAlpha', new Zend_Filter_Alpha());
		self::addRequirement('AsDigits', new Zend_Filter_Digits());
		self::addRequirement('AsHtmlEntities', new Zend_Filter_HtmlEntities());
		self::addRequirement('AsInt', new Zend_Filter_Int());
		self::addRequirement('StripNewlines', new Zend_Filter_StripNewlines());
		self::addRequirement('StringToLower', new Zend_Filter_StringToLower());
		self::addRequirement('StringToUpper', new Zend_Filter_StringToUpper());
		self::addRequirement('StripTags', new Zend_Filter_StripTags());
		
		// Stubs
		self::addRequirement('Required', new Shanty_Mongo_Validate_StubTrue());
		self::addRequirement('AsReference', new Shanty_Mongo_Validate_StubTrue());
		
		// Create requirement creators
		self::addRequirementCreator('/^GreaterThan([\d]+)$/', function($data) {
			return new Zend_Validate_GreaterThan($data[1]);
		});
		
		self::addRequirementCreator('/^LessThan([\d]+)$/', function($data) {
			return new Zend_Validate_LessThan($data[1]);
		});
		
		// Creates requirements to match classes
		$classValidator = function($data) {
			if (!class_exists($data[1])) return null;
			
			return new Shanty_Mongo_Validate_Class($data[1]);
		};
		
		self::addRequirementCreator('/^Document:([A-Za-z][\w\-]*)$/', $classValidator);
		self::addRequirementCreator('/^DocumentSet:([A-Za-z][\w\-]*)$/', $classValidator);
	}
	
	/**
	 * Get the requirement matching the name provided
	 *
	 * @param $name String Name of requirement
	 * @return mixed
	 **/
	public static function getRequirement($name)
	{
		// Requirement is already initialised return it
		if (array_key_exists($name, self::$_requirements)) {
			return self::$_requirements[$name];
		}
		
		// Attempt to create requirement
		if (!$requirement = self::createRequirement($name)) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No requirement exists for '{$name}'");
		}
		
		// Requirement found. Store it for later use
		self::addRequirement($name, $requirement);
		
		return self::$_requirements[$name];
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
	protected static function createRequirement($name)
	{
		// Match requirement name against regex's
		foreach (self::$_requirementCreators as $regex => $function) {
			$matches = array();
			preg_match($regex, $name, $matches);
				
			if (!empty($matches)) {
				return $function($matches);
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