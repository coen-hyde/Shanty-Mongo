<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
 
class Shanty_Mongo_TestCollection extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		require_once 'My/ShantyMongo/User.php';
		require_once 'My/ShantyMongo/Users.php';
		require_once 'My/ShantyMongo/Name.php';
		require_once 'My/ShantyMongo/Student.php';
	}
	
	public function testGetDbName()
	{
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, My_ShantyMongo_User::getDbName());
	}
	
	public function testGetCollectionName()
	{
		$this->assertEquals('user', My_ShantyMongo_User::getCollectionName());
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
		$this->assertEquals('My_ShantyMongo_Users', My_ShantyMongo_User::getDocumentSetClass());
		$this->assertEquals('Shanty_Mongo_DocumentSet', My_ShantyMongo_Name::getDocumentSetClass());
	}
	
	public function testMakeRequirementsTidy()
	{
		$dirty = array(
			'name' => array('Document:My_ShantyMongo_Name', 'Required'),
			'friends' => 'DocumentSet',
			'friends.$' => array('Document:User', 'AsReference'),
			'sex' => array('Required', 'Validator:InArray' => array('female', 'male')),
		);
		
		$clean = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:User' => null, 'AsReference' => null),
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
			'friends.$' => array('Document:User' => null, 'AsReference' => null),
		);
		
		$requirements2 = array(
			'email' => array('Required' => null),
			'concession' => array('Required' => null)
		);
		
		$result = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:User' => null, 'AsReference' => null),
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
			'first' => array('Required' => null),
			'last' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, My_ShantyMongo_Name::getCollectionRequirements());
	}
	
	/**
	 * @depends testMergeRequirements
	 */
	public function testGetInheritedCollectionRequirements()
	{
		$requirements = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postCode' => array('Required' => null),
			'friends' => array('DocumentSet' => null),
			'friends.$' => array('Document:User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('female', 'male')),
			'partner' => array('Document:User' => null, 'AsReference' => null),
			'concession' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, My_ShantyMongo_Student::getInheritedCollectionRequirements());
	}
}