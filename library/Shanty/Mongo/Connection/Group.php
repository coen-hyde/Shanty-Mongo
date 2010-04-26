<?php

require_once 'Shanty/Mongo/Connection.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Connection_Group
{
	protected $_masters = null;
	protected $_slaves = null;
	
	public function __construct(array $connectionOptions = null)
	{
		$this->_masters = new Shanty_Mongo_Connection_Stack();
		$this->_slaves = new Shanty_Mongo_Connection_Stack();
		
		// add connections
		if (!is_null($connectionOptions)) {
			$this->addConnections($connectionOptions);
		}
	}
	
	/**
	 * Add multiple connections at once using arrays of options
	 * 
	 * @param array $connectionOptions
	 */
	public function addConnections(array $connectionOptions)
	{
		$masters = array();
		$slaves = array();
		
		// Lets add our masters
		if (array_key_exists('master', $connectionOptions)) $masters[] = $connectionOptions['master']; // single master
		elseif (array_key_exists('masters', $connectionOptions)) $masters = $connectionOptions['masters']; // multiple masters
		else $masters[] = $connectionOptions; // one server
		
		foreach ($masters as $masterConnectionOptions) {
			$connection = new Shanty_Mongo_Connection($this->formatConnectionString($masterConnectionOptions));
			if (array_key_exists('weight', $masterConnectionOptions)) $weight = (int) $masterConnectionOptions['weight'];
			else $weight = 1;
			
			$this->addMaster($connection, $weight);
		}
		
		// Lets add our slaves
		if (array_key_exists('slave', $connectionOptions)) $slaves[] = $connectionOptions['slave']; // single slave
		elseif (array_key_exists('slaves', $connectionOptions)) $slaves = $connectionOptions['slaves']; // multiple slaves
		
		foreach ($slaves as $slaveConnectionOptions) {
			$connection = new Shanty_Mongo_Connection($this->formatConnectionString($slaveConnectionOptions));
			if (array_key_exists('weight', $slaveConnectionOptions)) $weight = (int) $slaveConnectionOptions['weight'];
			else $weight = 1;
			
			$this->addSlave($connection, $weight);
		}
	}
	
	/**
	 * Add a connection to a master server
	 * 
	 * @param Shanty_Mongo_Connection $connection
	 * @param int $weight
	 */
	public function addMaster(Shanty_Mongo_Connection $connection, $weight = 1)
	{
		$this->_masters->addNode($connection, $weight);
	}
	
	/**
	 * Get all master connections
	 * 
	 * @return Shanty_Mongo_Connection_Stack
	 */
	public function getMasters()
	{
		return $this->_masters;
	}
	
	/**
	 * Add a connection to a slaver server
	 * 
	 * @param $connection
	 * @param $weight
	 */
	public function addSlave(Shanty_Mongo_Connection $connection, $weight = 1)
	{
		$this->_slaves->addNode($connection, $weight);
	}
	
	/**
	 * Get all slave connections
	 * 
	 * @return Shanty_Mongo_Connection_Stack
	 */
	public function getSlaves()
	{
		return $this->_slaves;
	}
	
	/**
	 * Get a write connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public function getWriteConnection($connectionGroup = 'default')
	{
		// Select master
		$write = $this->_masters->selectNode();
		
		// Connect to db
		$write->connect();
		
		return $write;
	}
	
	/**
	 * Get a read connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public function getReadConnection($connectionGroup = 'default')
	{
		if (count($this->_slaves) === 0) {
			// If no slaves then get a master connection
			$read = $this->getWriteConnection();
		}
		else {
			// Select slave
			$read = $this->_slaves->selectNode();
			
			// Connect to db
			$read->connect();
		}
		
		return $read;
	}
	
	/**
	 * Format a connection string
	 * 
	 * @param array $connectionOptions
	 * 
	 */
	public function formatConnectionString(array $connectionOptions = array())
	{
		// See if we are dealing with a replica pair
		if (array_key_exists('replica_pair', $connectionOptions)) $hosts = $connectionOptions['replica_pair'];
		else $hosts = array($connectionOptions);
		
		$connectionString = 'mongodb://';
		
		$hostStringList = array();
		foreach ($hosts as $hostOptions) {
			$hostStringList[] = static::formatHostString($hostOptions);
		}
		
		$connectionString .= implode(',', $hostStringList);
		
		return $connectionString;
	}
	
	/**
	 * Format a host string
	 * 
	 * @param $options
	 * @return string
	 */
	public function formatHostString(array $hostOptions = array())
	{
		$hostString = '';
		
		// Set username
		if (isset($hostOptions['username']) && !is_null($hostOptions['username'])) {
			$hostString .= $hostOptions['username'];
			
			// Set password
			if (isset($hostOptions['password']) && !is_null($hostOptions['password'])) {
				$hostString .= ':'.$hostOptions['password'];
			}
			
			$hostString .= '@';
		}
		
		// Set host
		if (isset($hostOptions['host']) && !is_null($hostOptions['host'])) $hostString .= $hostOptions['host'];
		else $hostString .= '127.0.0.1';
		
		// Set port
		$hostString .= ':';
		if (isset($hostOptions['port']) && !is_null($hostOptions['port'])) $hostString .= $hostOptions['port'];
		else $hostString .= '27017';
		
		return $hostString;
	}
}