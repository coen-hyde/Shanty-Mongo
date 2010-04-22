<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Connection/Group.php';
require_once 'Shanty/Mongo/Connection/Stack.php';
 
class Shanty_Mongo_Connection_GroupTest extends PHPUnit_Framework_TestCase
{
	protected $_group;
	
	public function setUp()
	{
		$this->_group = new Shanty_Mongo_Connection_Group();
	}
	
	public function testFormatConnectionString()
	{
		$this->assertEquals('mongodb://127.0.0.1:27017', $this->_group->formatConnectionString());
		
		$options = array('host' => 'mongodb.local');
		$this->assertEquals("mongodb://{$options['host']}:27017", $this->_group->formatConnectionString($options));
		
		$options = array(
			'replica_pair' => array(
				array('host' => 'mongodb1.local'),
				array('host' => 'mongodb2.local'),
			)
		);
		$this->assertEquals("mongodb://{$options['replica_pair'][0]['host']}:27017,{$options['replica_pair'][1]['host']}:27017", $this->_group->formatConnectionString($options));
	}
	
	public function testFormatHostString()
	{
		 $this->assertEquals('127.0.0.1:27017', $this->_group->formatHostString());
		 
		 $options = array('host' => 'mongodb.local');
		 $this->assertEquals("{$options['host']}:27017", $this->_group->formatHostString($options));
		 
		 $options = array('port' => '27018');
		 $this->assertEquals("127.0.0.1:{$options['port']}", $this->_group->formatHostString($options));
		 
		 $options = array('username' => 'jerry', 'password' => 'springer');
		 $this->assertEquals("{$options['username']}:{$options['password']}@127.0.0.1:27017", $this->_group->formatHostString($options));
	}
	
	
}