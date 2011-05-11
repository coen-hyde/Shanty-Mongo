<?php

require_once 'Shanty/Mongo/Connection.php';
require_once 'Shanty/Mongo/Connection/Stack.php';

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
	public function addConnections($connectionOptions)
	{
		if ($connectionOptions instanceof Zend_Config) {
			$connectionOptions = $connectionOptions->toArray();
		}
		
		$masters = array();
		$masterStackOptions = array();
		$slaves = array();
		$slaveStackOptions = array();
		
		$group = $this;
		$addConnections = function(Shanty_Mongo_Connection_Stack $stack, array $connections) use ($group) {
			foreach ($connections as $connectionData) {
				$options = array_intersect_key($connectionData, array_flip(Shanty_Mongo_Connection::getAvailableOptions()));
				
				$connection = new Shanty_Mongo_Connection($group->formatConnectionString($connectionData), $options);
				if (array_key_exists('weight', $connectionData)) $weight = (int) $connectionData['weight'];
				else $weight = 1;
				
				$stack->addNode($connection, $weight);
			}
		};
		
		// Lets add our masters
		if (array_key_exists('master', $connectionOptions)) $masters[] = $connectionOptions['master']; // single master
		elseif (array_key_exists('masters', $connectionOptions)) {
			$connectionKeys = array_filter(array_keys($connectionOptions['masters']), 'is_numeric');
			$masters = array_intersect_key($connectionOptions['masters'], array_flip($connectionKeys)); // only connections
			$masterStackOptions = array_diff_key($connectionOptions['masters'], array_flip($connectionKeys)); // only options
		}
		else $masters[] = $connectionOptions; // one server
		
		$addConnections($this->getMasters(), $masters); // Add master connections
		$this->getMasters()->setOptions($masterStackOptions); // Set master stack options
		
		// Lets add our slaves
		if (array_key_exists('slave', $connectionOptions)) $slaves[] = $connectionOptions['slave']; // single slave
		elseif (array_key_exists('slaves', $connectionOptions)) {
			$connectionKeys = array_filter(array_keys($connectionOptions['slaves']), 'is_numeric');
			$slaves = array_intersect_key($connectionOptions['slaves'], array_flip($connectionKeys)); // only connections
			$slaveStackOptions = array_diff_key($connectionOptions['slaves'], array_flip($connectionKeys)); // only options
		}; 
		
		$addConnections($this->getSlaves(), $slaves); // Add slave connections
		$this->getSlaves()->setOptions($slaveStackOptions); // Set slave stack options
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
	public function getWriteConnection()
	{
		// Select master
		$write = $this->_masters->selectNode();
		if ($write && !$write->connected) {
                    $write->connect();
                }
		
		return $write;
	}
	
	/**
	 * Get a read connection
	 * 
	 * @return Shanty_Mongo_Connection
	 */
	public function getReadConnection()
	{
		if (count($this->_slaves) === 0) {
			// If no slaves then get a master connection
			$read = $this->getWriteConnection();
		}
		else {
			// Select slave
			$read = $this->_slaves->selectNode();
			if ($read) $read->connect();
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
		// See if we are dealing with a replica set
		if (array_key_exists('hosts', $connectionOptions)) $hosts = $connectionOptions['hosts'];
		else $hosts = array($connectionOptions);
		
		$connectionString = 'mongodb://';
		
		$hostStringList = array();
		foreach ($hosts as $hostOptions) {
			$hostStringList[] = static::formatHostString($hostOptions);
		}
		
		$connectionString .= implode(',', $hostStringList);

		// Set database
		if (isset($connectionOptions['database'])) $connectionString .= '/'.$connectionOptions['database'];

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