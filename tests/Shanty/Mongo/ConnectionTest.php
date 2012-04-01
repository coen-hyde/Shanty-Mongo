<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'Shanty/Mongo/Collection.php';

class Shanty_Mongo_ConnectionTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
	}

    public function testGetMasterServerInfo()
    {
        $infoArray = array(
            'version',
            'gitVersion',
            'sysInfo',
            'versionArray',
            'bits',
            'debug',
            'maxBsonObjectSize',
            'ok'
        );

        /* the info is always different, we just care that we get the right keys back */
        $this->assertEquals($infoArray, array_keys(My_ShantyMongo_User::getMasterServerInfo()));
    }

    public function testGetSlaveServerInfo()
    {
        $infoArray = array(
            'version',
            'gitVersion',
            'sysInfo',
            'versionArray',
            'bits',
            'debug',
            'maxBsonObjectSize',
            'ok'
        );

        /* the info is always different, we just care that we get the right keys back */
        $this->assertEquals($infoArray, array_keys(My_ShantyMongo_User::getSlaveServerInfo()));
    }

    public function testGetServerInfo()
    {
        $infoArray = array(
            'master',
            'slave',
        );

        $serverInfo = My_ShantyMongo_User::getServerInfo();

        /* the info is always different, we just care that we get the right keys back */
        $this->assertEquals($infoArray, array_keys($serverInfo));

        $infoArray = array(
            'version',
            'gitVersion',
            'sysInfo',
            'versionArray',
            'bits',
            'debug',
            'maxBsonObjectSize',
            'ok'
        );

        $this->assertEquals($infoArray, array_keys($serverInfo['slave']));
        $this->assertEquals($infoArray, array_keys($serverInfo['master']));
    }

	public function testGetAvailableOptions()
	{
		$options = array(
			'persist',
			'timeout',
			'replicaSet'
		);
		$this->assertEquals($options, Shanty_Mongo_Connection::getAvailableOptions());
	}

	public function testParseHostString()
	{
		$hostInfo = array(
			'host' => 'localhost'
		);

		$this->assertEquals($hostInfo, Shanty_Mongo_Connection::parseHostString('localhost'));

		$hostInfo = array(
			'host' => 'localhost',
			'port' => 27017
		);

		$this->assertEquals($hostInfo, Shanty_Mongo_Connection::parseHostString('localhost:27017'));

		$hostInfo = array(
			'host' => 'localhost',
			'username' => 'coen'
		);

		$this->assertEquals($hostInfo, Shanty_Mongo_Connection::parseHostString('coen@localhost'));

		$hostInfo = array(
			'username' => 'coen',
			'password' => 'pass',
			'host' => '127.0.0.1',
			'port' => '27017'
		);

		$this->assertEquals($hostInfo, Shanty_Mongo_Connection::parseHostString('coen:pass@127.0.0.1:27017'));		
	}

	/**
	 * @depends testParseHostString
	 */
	public function testParseConnectionString()
	{
		// Single host
		$connectionInfo = array(
			'connectionString' => 'mongodb://127.0.0.1:27017/shanty-mongo',
			'database' => 'shanty-mongo',
			'hosts' => array(
				array (
					'host' => '127.0.0.1',
					'port' => 27017
				)
			)
		);
		$this->assertEquals($connectionInfo, Shanty_Mongo_Connection::parseConnectionString('mongodb://127.0.0.1:27017/shanty-mongo'));

		// Multiple hosts
		$connectionInfo = array(
			'connectionString' => 'mongodb://127.0.0.1:27017,coen:pass@localhost:27018/shanty-mongo',
			'database' => 'shanty-mongo',
			'hosts' => array(
				array (
					'host' => '127.0.0.1',
					'port' => 27017
				),
				array (
					'username' => 'coen',
					'password' => 'pass',
					'host' => 'localhost',
					'port' => 27018
				)
			)
		);
		$this->assertEquals($connectionInfo, Shanty_Mongo_Connection::parseConnectionString('mongodb://127.0.0.1:27017,coen:pass@localhost:27018/shanty-mongo'));
	}

	/**
	 * @depends testParseConnectionString
	 */
	public function testGetDatabase()
	{
		$connection = new Shanty_Mongo_Connection('mongodb://127.0.0.1:27017/shanty-mongo');
		$this->assertEquals('shanty-mongo', $connection->getDatabase());
	}

	/**
	 * @depends testParseConnectionString
	 */
	public function testGetHosts()
	{
		$connection = new Shanty_Mongo_Connection('mongodb://127.0.0.1:27017/shanty-mongo');
		$hosts = array(
			array(
				'host' => '127.0.0.1',
				'port' => 27017
			)
		);
		
		$this->assertEquals($hosts, $connection->getHosts());
	}
}