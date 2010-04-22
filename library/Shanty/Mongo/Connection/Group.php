<?php

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
	protected $_reselectWrite = false;
	protected $_reselectRead = false;
	protected $_cachedWrite = null;
	protected $_cachedRead = null;
	
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
		if (array_key_exists('master', $connectionOptions)) {
			if (array_key_exists('host', $connectionOptions['master'])) $masters[] = $connectionOptions['master']; // single master
			else $masters = $connectionOptions['master']; // multiple masters
		}
		else $masters[] = $connectionOptions; // one server
		
		foreach ($masters as $connectionOptions) {
			$connection = new Shanty_Mongo_Connection($this->formatConnectionString($connectionOptions));
			if (array_key_exists('weight', $connectionOptions)) $weight = (int) $connectionOptions['weight'];
			else $weight = 1;
			
			$this->addMaster($connection, $weight);
		}
		
		// Lets add our slaves
		if (array_key_exists('slave', $connectionOptions)) {
			if (array_key_exists('host', $connectionOptions['slave'])) $slaves[] = $connectionOptions['slave']; // single slave
			else $masters = $connectionOptions['slave']; // multiple slaves
		}
		
		foreach ($slaves as $connectionOptions) {
			$connection = new Shanty_Mongo_Connection($this->formatConnectionString($connectionOptions));
			if (array_key_exists('weight', $connectionOptions)) $weight = (int) $connectionOptions['weight'];
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
		// If a connection is cached then return it
		if (!is_null($this->_cachedWrite)) {
			return $this->_cachedWrite;
		}
		
		// If no master connections throw an exception
		if (count($this->_masters) === 0) {
			require_once 'Shanty/Mongo/Exception.php';
			throw new Shanty_Mongo_Exception("No master connections available for selection");
		}
		
		$write = $this->_masters->selectNode();
		
		// Should we remember this connection?
		if ($this->_reselectWrite) {
			$this->_cachedWrite = $write;
		}
		
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
		// If a connection is cached then return it
		if (!is_null($this->_cachedRead)) {
			return $this->_cachedRead;
		}
		
		// If no slaves then get a master connection
		if (count($this->_slaves) === 0) {
			$read = $this->getWriteConnection();
		}
		else $read = $this->_slaves->selectNode();

		// Should we remember this connection?
		if (!$this->_reselectRead) {
			$this->_cachedRead = $read;
		}
		
		$read->connect();
		
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