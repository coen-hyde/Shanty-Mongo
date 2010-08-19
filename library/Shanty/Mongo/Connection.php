<?php

require_once 'Shanty/Mongo.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Connection extends Mongo
{
	static protected $_availableOptions = array(
		'persist',
		'timeout',
		'replicaSet'
	);
	
	protected $_connectionInfo = array();
	
	public function __construct($connectionString = null, array $options = array())
	{
		Shanty_Mongo::init();
		
		// Set the server to local host if one was not provided
		if (is_null($connectionString)) $connectionString = '127.0.0.1';
		
		$options['connect'] = false;
		
		$this->_connectionInfo = $options;
		$this->_connectionInfo['connectionString'] = $connectionString;
		
		return parent::__construct($connectionString, $options);
	}
	
	public function getConnectionInfo()
	{
		return $this->_connectionInfo;
	}
	
	/**
	 * Get available options
	 * 
	 * @return array
	 */
	static public function getAvailableOptions()
	{
		return static::$_availableOptions;
	}
}