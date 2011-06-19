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

		// Force mongo to connect only when we need to
		$options['connect'] = false;
		$connectionInfo = self::parseConnectionString($connectionString);
		
		$this->_connectionInfo = array_merge($options, $connectionInfo);

		return parent::__construct($connectionString, $options);
	}

	/**
	 * Get some info about this connection
	 *
	 * @return array
	 */
	public function getConnectionInfo()
	{
		return $this->_connectionInfo;
	}

	/**
	 * Get the actual connection string used for this connection. This differs from __toString in
	 * that __toString returns a string representation of the connection, not the connection string used
	 *
	 * @return array
	 */
	public function getActualConnectionString()
	{
		return $this->_connectionInfo['connectionString'];
	}
	
	/**
	 * Get the database this connection is connection to
	 *
	 * @return string
	 */
	public function getDatabase()
	{
		if (!isset($this->_connectionInfo['database'])) return null;

		return $this->_connectionInfo['database'];
	}

	/**
	 * Get a list of the hosts this connection is connection to
	 *
	 * @return array
	 */
	public function getHosts()
	{
		return $this->_connectionInfo['hosts'];
	}

	/**
	 * Parse the connection string
	 *
	 * @param  $connectionString
	 * @return array
	 */
	public static function parseConnectionString($connectionString)
	{
		$connectionInfo = array(
			'connectionString' => $connectionString
		);

		// Remove mongodb protocol string
		if (substr($connectionString, 0, 10) == 'mongodb://') {
			$connectionString = substr($connectionString, 10);
		}

		// Is there a database name
		if ($pos = strrpos($connectionString, '/')) {
			$connectionInfo['database'] = substr($connectionString, $pos+1);
			$connectionString = substr($connectionString, 0, $pos);
		}

		// Fetch Hosts
		if (!empty($connectionString)) {
			$hostStrings = explode(',', $connectionString);
			$connectionInfo['hosts'] = array();
			foreach ($hostStrings as $hostString) {
				$connectionInfo['hosts'][] = self::parseHostString($hostString);
			}
		}
		
		return $connectionInfo;
	}

	/**
	 * Parse a host string
	 *
	 * @param  $hostString
	 * @return array
	 */
	public static function parseHostString($hostString)
	{
		$hostInfo = array();

		// Are we authenticating with a username and password?
		if ($pos = strpos($hostString, '@')) {
			$authString = substr($hostString, 0, $pos);

			$data = explode(':', $authString);
			$hostInfo['username'] = $data[0];

			if (count($data) > 1) {
				$hostInfo['password'] = $data[1];
			}
			
			$hostString = substr($hostString, $pos+1);
		}

		// Grab host and port
		$data = explode(':', $hostString);
		$hostInfo['host'] = $data[0];

		if (count($data) > 1) {
			$hostInfo['port'] = $data[1];
		}

		return $hostInfo;
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