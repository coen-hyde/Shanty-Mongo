<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
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
			'sex' => array('Required', 'Validator:InArray' => array('female', 'male')),
		);
		
		$clean = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('female', 'male')),
		);
		
		$this->assertEquals($clean, Shanty_Mongo_Collection::makeRequirementsTidy($dirty));
	}
	
	public function testMergeRequirements()
	{
		$requirements1 = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Validator:EmailAddress' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
		);
		
		$requirements2 = array(
			'email' => array('Required' => null),
			'concession' => array('Required' => null)
		);
		
		$result = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'concession' => array('Required' => null),
		);
		
		$this->assertEquals($result, Shanty_Mongo_Collection::mergeRequirements($requirements1, $requirements2));
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
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postCode' => array('Required' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('female', 'male')),
			'partner' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'concession' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, My_ShantyMongo_Student::getCollectionRequirements());
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postCode' => array('Required' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('female', 'male')),
			'partner' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null)
		);
		
		// This assertion is needed to ensure parent requirements have not been contaminated by child requirements
		$this->assertEquals($requirements, My_ShantyMongo_User::getCollectionRequirements());
	}
	
	public function testGetMongoDb()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getMongoDb());
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getMongoDb()->__toString());
		
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addSlave($connection);
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getMongoDb(false));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getMongoDb(false)->__toString());
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
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, My_ShantyMongo_User::getMongoCollection());
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
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $document);
		$this->assertEquals('My_ShantyMongo_User', get_class($document));
		$this->assertEquals('user', $document->getCollection());
		$this->assertTrue($document->isNewDocument());
		
		$document = My_ShantyMongo_User::create(array('email' => 'address@domain.com'), false);
		$this->assertEquals('address@domain.com', $document->email);
		$this->assertFalse($document->isNewDocument());
	}
	
	public function testFind()
	{
		$cherry = My_ShantyMongo_User::find('4c04516f1f5f5e21361e3ab1');

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_User', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->name->first);
		$this->assertEquals($this->_users['cherry'], $cherry->export());
		$this->assertFalse($cherry->isNewDocument());
		$this->assertEquals('user', $cherry->getCollection());
		
		$cherry = My_ShantyMongo_User::find(new MongoId('4c04516f1f5f5e21361e3ab1'));

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_User', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->name->first);
		$this->assertEquals($this->_users['cherry'], $cherry->export());
	}
	
	public function testOne()
	{
		$roger = My_ShantyMongo_User::one(array('name.first' => 'Roger'));

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $roger);
		$this->assertEquals('My_ShantyMongo_User', get_class($roger));
		$this->assertEquals('Roger', $roger->name->first);
		$this->assertEquals($this->_users['roger'], $roger->export());
	}

	public function testFetchOne()
	{
		$roger = My_ShantyMongo_User::fetchOne(array('name.first' => 'Roger'));

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $roger);
		$this->assertEquals('My_ShantyMongo_User', get_class($roger));
		$this->assertEquals('Roger', $roger->name->first);
		$this->assertEquals($this->_users['roger'], $roger->export());
	}
	
	public function testAll()
	{
		$users = My_ShantyMongo_User::all();
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $users);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($users));
		$this->assertEquals(3, $users->count());
		
		$males = My_ShantyMongo_User::all(array('sex' => 'M'));
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $males);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($males));
		$this->assertEquals(2, $males->count());
	}
	
	public function testFetchAll()
	{
		$users = My_ShantyMongo_User::fetchAll();
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $users);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($users));
		$this->assertEquals(3, $users->count());
		
		$males = My_ShantyMongo_User::fetchAll(array('sex' => 'M'));
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $males);
		$this->assertEquals('Shanty_Mongo_Iterator_Cursor', get_class($males));
		$this->assertEquals(2, $males->count());
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
		
		My_ShantyMongo_User::insert($sarah);
		
		$user = My_ShantyMongo_User::find('4c04d5101f5f5e21361e3ab5');
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $user);
		$this->assertEquals('My_ShantyMongo_User', get_class($user));
		$this->assertEquals('Sarah', $user->name->first);
		
		
		$users = My_ShantyMongo_User::all();
		$this->assertEquals(4, $users->count());
	}
	
	/**
	 * @depends testFind
	 */
	public function testUpdate()
	{
		My_ShantyMongo_User::update(array('_id' => new MongoId('4c04516f1f5f5e21361e3ab1')), array('$set' => array('name.first' => 'Lauren')));
		
		$lauren = My_ShantyMongo_User::find('4c04516f1f5f5e21361e3ab1');
		$this->assertEquals('Lauren', $lauren->name->first);
	}
	
	/**
	 * @depends testFind
	 */
	public function testRemove()
	{
		My_ShantyMongo_User::remove(array('name.first' => 'Bob'));
		
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
		$indexInfo = array(
			array(
				'name' => '_id_',
				'ns' => 'shanty-mongo-testing.user',
				'key' => array(
					'_id' => 1
				)
			)
		);
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
	
	public function testEnsureIndex()
	{
		My_ShantyMongo_User::ensureIndex(array('name.first' => 1));
		
		$indexInfo = array(
			array(
				'name' => '_id_',
				'ns' => 'shanty-mongo-testing.user',
				'key' => array(
					'_id' => 1
				)
			),
			array(
				'_id' => new MongoId(),
				'ns' => 'shanty-mongo-testing.user',
				'key' => array(
					'name.first' => 1
				),
				'name' => 'name_first_1',
			)
		);
		
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
	
	/**
	 * @depends testEnsureIndex
	 */
	public function testDeleteIndex()
	{
		My_ShantyMongo_User::ensureIndex(array('name.first' => 1));
		My_ShantyMongo_User::deleteIndex('name.first');
		
		$indexInfo = array(
			array(
				'name' => '_id_',
				'ns' => 'shanty-mongo-testing.user',
				'key' => array(
					'_id' => 1
				)
			)
		);
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
	
	/**
	 * @depends testEnsureIndex
	 */
	public function testDeleteIndexes()
	{
		My_ShantyMongo_User::ensureIndex(array('name.first' => 1));
		My_ShantyMongo_User::deleteIndexes();
		
		$indexInfo = array(
			array(
				'name' => '_id_',
				'ns' => 'shanty-mongo-testing.user',
				'key' => array(
					'_id' => 1
				)
			)
		);
		
		$this->assertEquals($indexInfo, My_ShantyMongo_User::getIndexInfo());
	}
}