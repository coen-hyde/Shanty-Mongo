<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

//require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
 
class Shanty_Mongo_CollectionTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
	}
	
	public function testGetDbName()
	{
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getDbName());

		Shanty_Mongo::removeConnectionGroups();

		$connection = new Shanty_Mongo_Connection('localhost/shanty-mongo');
		Shanty_Mongo::addMaster($connection);

		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getDbName());
		$this->assertEquals('shanty-mongo', My_ShantyMongo_Name::getDbName());
	}
	
	public function testGetCollectionName()
	{
		$this->assertEquals('user', My_ShantyMongo_User::getCollectionName());
	}
	
	public function testGetConnectionGroupName()
	{
		$this->assertEquals('default', My_ShantyMongo_User::getConnectionGroupName());
	}
	
	public function testHasDbName()
	{
		$this->assertTrue(My_ShantyMongo_User::hasDbName());
		$this->assertFalse(My_ShantyMongo_Name::hasDbName());
	}
	
	public function testHasCollectionName()
	{
		$this->assertTrue(My_ShantyMongo_User::hasCollectionName());
		$this->assertFalse(My_ShantyMongo_Name::hasCollectionName());
	}
	
	public function testIsDocumentClass()
	{
		$this->assertFalse(Shanty_Mongo_Collection::isDocumentClass());
		$this->assertTrue(My_ShantyMongo_User::isDocumentClass());
	}
	
	public function testGetDocumentClass()
	{
		$this->assertEquals('My_ShantyMongo_User', My_ShantyMongo_User::getDocumentClass());
		$this->assertEquals('My_ShantyMongo_Name', My_ShantyMongo_Name::getDocumentClass());
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetDocumentClassException()
	{
		Shanty_Mongo_Collection::getDocumentClass();
	}
	
	public function testGetDocumentSetClass()
	{
		$this->assertEquals('Shanty_Mongo_DocumentSet', My_ShantyMongo_Name::getDocumentSetClass());
	}
	
	public function testMakeRequirementsTidy()
	{
		$dirty = array(
			'name' => array('Document:My_ShantyMongo_Name', 'Required'),
			'friends' => 'DocumentSet',
			'friends.$' => array('Document:My_ShantyMongo_User', 'AsReference'),
			'sex' => array('Required', 'Validator:InArray' => array('F', 'M')),
		);
		
		$clean = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('F', 'M')),
		);
		
		$this->assertEquals($clean, Shanty_Mongo_Collection::makeRequirementsTidy($dirty));
	}
	
	public function testMergeRequirements()
	{
		$requirements1 = array(
			'name' => array('Validator:Magic' => null, 'Document' => null, 'Required' => null),
			'email' => array('Validator:EmailAddress' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
		);
		
		$requirements2 = array(
			'name' => array('Document:My_ShantyMongo_Name' => null),
			'email' => array('Required' => null),
			'concession' => array('Required' => null),
			'friends' => array('DocumentSet' => null)
		);
		
		$result = array(
			'name' => array('Validator:Magic' => null, 'Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Validator:EmailAddress' => null, 'Required' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'concession' => array('Required' => null),
		);
		
		$this->assertEquals($result, Shanty_Mongo_Collection::mergeRequirements($requirements1, $requirements2));
	}
	
	public function testGetCollectionInheritance()
	{
		$this->assertEquals(array('My_ShantyMongo_User'), My_ShantyMongo_User::getCollectionInheritance());
		
		$inheritance = My_ShantyMongo_Student::getCollectionInheritance();
		$this->assertEquals('My_ShantyMongo_Student', $inheritance[0]);
		$this->assertEquals('My_ShantyMongo_User', $inheritance[1]);
		
		$inheritance = My_ShantyMongo_ArtStudent::getCollectionInheritance();
		$this->assertEquals('My_ShantyMongo_ArtStudent', $inheritance[0]);
		$this->assertEquals('My_ShantyMongo_Student', $inheritance[1]);
		$this->assertEquals('My_ShantyMongo_User', $inheritance[2]);
	}
	
	/**
	 * @depends testMakeRequirementsTidy
	 */
	public function testGetCollectionRequirements()
	{
		$requirements = array(
			'concession' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, My_ShantyMongo_Student::getCollectionRequirements(false));
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.street' => array('Required' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postcode' => array('Required' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('F', 'M')),
			'partner' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'concession' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, My_ShantyMongo_Student::getCollectionRequirements());
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.street' => array('Required' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postcode' => array('Required' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('F', 'M')),
			'partner' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null)
		);
		
		// This assertion is needed to ensure parent requirements have not been contaminated by child requirements
		$this->assertEquals($requirements, My_ShantyMongo_User::getCollectionRequirements());
	}

	public function testGetConnection()
	{
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addSlave($connection);

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getConnection());
		$this->assertEquals(TESTS_SHANTY_MONGO_CONNECTIONSTRING, My_ShantyMongo_User::getConnection()->getActualConnectionString());

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getConnection(false));
		$this->assertEquals('localhost', My_ShantyMongo_User::getConnection(false)->getActualConnectionString());
	}

	public function testGetMongoDb()
	{
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getMongoDb());
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getMongoDb()->__toString());
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetMongoDbException()
	{
		My_ShantyMongo_Name::getMongoDb();
	}
	
	public function testGetMongoCollection()
	{
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getMongoCollection());
		$this->assertEquals(TESTS_SHANTY_MONGO_DB.'.user', My_ShantyMongo_User::getMongoCollection()->__toString());
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetMongoCollectionException()
	{
		My_ShantyMongo_Name::getMongoCollection();
	}
	
	public function testCreate()
	{
		$document = My_ShantyMongo_User::create();
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $document);
		$this->assertEquals('My_ShantyMongo_User', get_class($document));
		$this->assertEquals('user', $document->getConfigAttribute('collection'));
		$this->assertTrue($document->isNewDocument());
		
		$document = My_ShantyMongo_User::create(array('email' => 'address@domain.com'), false);
		$this->assertEquals('address@domain.com', $document->email);
		$this->assertFalse($document->isNewDocument());
	}
	
	public function testFind()
	{
		$cherry = My_ShantyMongo_User::find('4c04516f1f5f5e21361e3ab1');

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_Student', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->name->first);
		$this->assertEquals($this->_users['cherry'], $cherry->export());
		$this->assertFalse($cherry->isNewDocument());
		$this->assertEquals('user', $cherry->getConfigAttribute('collection'));
		
		$cherry = My_ShantyMongo_User::find(new MongoId('4c04516f1f5f5e21361e3ab1'));

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_Student', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->name->first);
		$this->assertEquals($this->_users['cherry'], $cherry->export());
	}
	
	public function testOne()
	{
		$roger = My_ShantyMongo_User::one(array('name.first' => 'Roger'));

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $roger);
		$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($roger));
		$this->assertEquals('Roger', $roger->name->first);
		$this->assertEquals($this->_users['roger'], $roger->export());

		// Find only rodger's name and email
		$roger = My_ShantyMongo_User::one(array('name.first' => 'Roger'), array('name' => 1, 'email' => 1));
		$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($roger));
		$this->assertEquals(4, count($roger));
		$this->assertEquals(array('_id', '_type', 'name', 'email'), $roger->getPropertyKeys());
		$this->assertEquals('Roger', $roger->name->first);
		$this->assertNull($roger->sex);

		// No teacher by the name of roger exists
		$roger = My_ShantyMongo_Teacher::fetchOne(array('name.first' => 'Roger'));
		$this->assertNull($roger);
	}

	public function testFetchOne()
	{
		$roger = My_ShantyMongo_User::fetchOne(array('name.first' => 'Roger'), array('name' => 1, 'email' => 1));

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $roger);
		$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($roger));
		$this->assertEquals(array('_id', '_type', 'name', 'email'), $roger->getPropertyKeys());
		$this->assertEquals('Roger', $roger->name->first);
		$this->assertNull($roger->sex);
	}
	
	public function testAll()
	{
		$users = My_ShantyMongo_User::all();
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $users);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($users));
		$this->assertEquals(3, $users->count());
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $users->getNext()->getId()->__toString());
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $users->getNext()->getId()->__toString());
		$this->assertEquals('4c0451791f5f5e21361e3ab2', $users->getNext()->getId()->__toString());
		
		$males = My_ShantyMongo_User::all(array('sex' => 'M'));
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $males);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($males));
		$this->assertEquals(2, $males->count());
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $males->getNext()->getId()->__toString());
		$this->assertEquals('4c0451791f5f5e21361e3ab2', $males->getNext()->getId()->__toString());
		
		// Test inheritance
		$students = My_ShantyMongo_Student::all();
		$this->assertEquals(2, $students->count());
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $students->getNext()->getId()->__toString());
		$this->assertEquals('4c0451791f5f5e21361e3ab2', $students->getNext()->getId()->__toString());
		
		$artstudents = My_ShantyMongo_ArtStudent::all();
		$this->assertEquals(1, $artstudents->count());
		$this->assertEquals('4c0451791f5f5e21361e3ab2', $artstudents->getNext()->getId()->__toString());

		// Test loading of partial documents
		$users = My_ShantyMongo_User::all(array(), array('name' => 1, 'email' => 1));
		$firstUser = $users->getNext();
		$this->assertEquals(array('_id', '_type', 'name', 'email'), $firstUser->getPropertyKeys());
		$this->assertNull($firstUser->sex);
	}
	
	public function testFetchAll()
	{
		$users = My_ShantyMongo_User::fetchAll();
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $users);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($users));
		$this->assertEquals(3, $users->count());
		
		$males = My_ShantyMongo_User::fetchAll(array('sex' => 'M'), array('name' => 1, 'email' => 1));
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $males);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($males));
		$this->assertEquals(2, $males->count());
		$firstUser = $males->getNext();
		$this->assertEquals(array('_id', '_type', 'name', 'email'), $firstUser->getPropertyKeys());
		$this->assertNull($firstUser->sex);
	}
	
	public function testDistinct()
	{
		$distinctSexes = My_ShantyMongo_User::distinct('sex');
		$this->assertEquals(array('M', 'F'), $distinctSexes);
	}
	
	public function testInsert()
	{
		$sarah = array(
			'_id' => new MongoId('4c04d5101f5f5e21361e3ab5'),
			'name' => array(
				'first' => 'Sarah',
				'last' => 'Thomas',
			),
			'email' => 'bob.jones@domain.com',
			'sex' => 'M'
		);
		
		My_ShantyMongo_User::insert($sarah, array('safe' => true));
		
		$user = My_ShantyMongo_User::find('4c04d5101f5f5e21361e3ab5');
		
		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $user);
		$this->assertEquals('My_ShantyMongo_User', get_class($user));
		$this->assertEquals('Sarah', $user->name->first);
		
		
		$users = My_ShantyMongo_User::all();
		$this->assertEquals(4, $users->count());
	}

	public function testInsertBatch()
	{
		$data = array(
			array(
				'_id' => new MongoId('4c04d5101f5f5e21361e3ab6'),
				'name' => 'green',
				'hex' => '006600'
			),
			array(
				'_id' => new MongoId('4c04d5101f5f5e21361e3ab7'),
				'name' => 'blue',
				'hex' => '0000CC'
			),
			array(
				'_id' => new MongoId('4c04d5101f5f5e21361e3ab8'),
				'name' => 'red',
				'hex' => 'FF0000'
			),
		);

		My_ShantyMongo_Simple::insertBatch($data, array('safe' => true));
		$colours = My_ShantyMongo_Simple::all();

		$colour = $colours->getNext();
		$this->assertEquals('green', $colour->name);
		$this->assertEquals(3, $colours->count());
	}
	
	/**
	 * @depends testFind
	 */
	public function testUpdate()
	{
		My_ShantyMongo_User::update(array('_id' => new MongoId('4c04516f1f5f5e21361e3ab1')), array('$set' => array('name.first' => 'Lauren')), array('safe' => true));
		
		$lauren = My_ShantyMongo_User::find('4c04516f1f5f5e21361e3ab1');
		$this->assertEquals('Lauren', $lauren->name->first);
	}
	
	/**
	 * @depends testFind
	 */
	public function testRemove()
	{
		My_ShantyMongo_User::remove(array('name.first' => 'Bob'), array('safe' => true));
		
		$bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		
		$this->assertNull($bob);
		
		$users = My_ShantyMongo_User::all();
		$this->assertEquals(2, $users->count());
	}
	
	public function testDrop()
	{
		My_ShantyMongo_User::drop();
		
		$users = My_ShantyMongo_User::all();
		
		$this->assertEquals(0, $users->count());
	}
	
	public function testGetIndexInfo()
	{
        /* as of mongod-2.x v=1 for indexes */
        $serverInfo = My_ShantyMongo_Admin::getMongoDb(true)->command(array('buildinfo' => 1));

        if((int)$serverInfo['versionArray'][0] == 2)
            $indexInfo = array(
                array(
                    'v' => 1,
                    'key' => array(
                        '_id' => 1
                    ),
                    'ns' => 'shanty-mongo-testing.user',
                    'name' => '_id_',
                )
            );
        else
            $indexInfo = array(
                array(
                    'name' => '_id_',
                    'ns' => 'shanty-mongo-testing.user',
                    'key' => array(
                        '_id' => 1
                    ),
                    'v' => 0
                )
            );


		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
	
	public function testEnsureIndex()
	{
        My_ShantyMongo_User::ensureIndex(array('name.first' => 1), array('safe' => true));

        /* as of mongod-2.x v=1 for indexes */
        $serverInfo = My_ShantyMongo_Admin::getMongoDb(true)->command(array('buildinfo' => 1));

        if((int)$serverInfo['versionArray'][0] == 2)
            $indexInfo = array(
                array(
                    'v' => 1,
                    'key' => array(
                        '_id' => 1
                    ),
                    'ns' => 'shanty-mongo-testing.user',
                    'name' => '_id_',
                ),
                array(
                    'v' => 1,
                    'key' => array(
                        'name.first' => 1
                    ),
                    'ns' => 'shanty-mongo-testing.user',
                    'name' => 'name_first_1',
                    '_id' => new MongoId(),
                )
            );
        else
            $indexInfo = array(
                array(
                    'name' => '_id_',
                    'ns' => 'shanty-mongo-testing.user',
                    'key' => array(
                        '_id' => 1
                    ),
                    'v' => 0
                ),
                array(
                    '_id' => new MongoId(),
                    'ns' => 'shanty-mongo-testing.user',
                    'key' => array(
                        'name.first' => 1
                    ),
                    'name' => 'name_first_1',
                    'v' => 0
                )
            );
		
		$retrievedIndexInfo = My_ShantyMongo_User::getIndexInfo();

		$this->assertEquals($indexInfo[0], $retrievedIndexInfo[0]);

        if((int)$serverInfo['versionArray'][0] == 1)
        {
		    $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $retrievedIndexInfo[1]['_id']);
		    $this->assertEquals('MongoId', get_class($retrievedIndexInfo[1]['_id']));
        }

		$this->assertEquals($indexInfo[1]['ns'], $retrievedIndexInfo[1]['ns']);
		$this->assertEquals($indexInfo[1]['key'], $retrievedIndexInfo[1]['key']);
		$this->assertEquals($indexInfo[1]['name'], $retrievedIndexInfo[1]['name']);
	}
	
	/**
	 * @depends testEnsureIndex
	 */
	public function testDeleteIndex()
	{
		My_ShantyMongo_User::ensureIndex(array('name.first' => 1), array('safe' => true));
		My_ShantyMongo_User::deleteIndex('name.first');

        /* as of mongod-2.x v=1 for indexes */
        $serverInfo = My_ShantyMongo_Admin::getMongoDb(true)->command(array('buildinfo' => 1));

        if((int)$serverInfo['versionArray'][0] == 2)
            $indexInfo = array(
                array(
                    'v' => 1,
                    'key' => array(
                        '_id' => 1
                    ),
                    'ns' => 'shanty-mongo-testing.user',
                    'name' => '_id_',
                )
            );
        else
            $indexInfo = array(
                array(
                    'name' => '_id_',
                    'ns' => 'shanty-mongo-testing.user',
                    'key' => array(
                        '_id' => 1
                    ),
                    'v' => 0
                )
            );
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
	
	/**
	 * @depends testEnsureIndex
	 */
	public function testDeleteIndexes()
	{
		My_ShantyMongo_User::ensureIndex(array('name.first' => 1), array('safe' => true));
		My_ShantyMongo_User::deleteIndexes();
		
		/* as of mongod-2.x v=1 for indexes */
        $serverInfo = My_ShantyMongo_Admin::getMongoDb(true)->command(array('buildinfo' => 1));

        if((int)$serverInfo['versionArray'][0] == 2)
            $indexInfo = array(
                array(
                    'v' => 1,
                    'key' => array(
                        '_id' => 1
                    ),
                    'ns' => 'shanty-mongo-testing.user',
                    'name' => '_id_',
                )
            );
        else
            $indexInfo = array(
                array(
                    'name' => '_id_',
                    'ns' => 'shanty-mongo-testing.user',
                    'key' => array(
                        '_id' => 1
                    ),
                    'v' => 0
                )
            );
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
}