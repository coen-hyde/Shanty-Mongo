<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/Document.php';
 
class Shanty_Mongo_TestDocument extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		$this->_bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
	}
	
	public function testGetRequirements()
	{
		$requirements = array(
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'email' => array('Required' => null, 'Validator:EmailAddress' => null),
			'addresses' => array('DocumentSet' => null),
			'addresses.$.state' => array('Required' => null),
			'addresses.$.suburb' => array('Required' => null),
			'addresses.$.postCode' => array('Required' => null),
			'friends' => array('DocumentSet:My_ShantyMongo_Users' => null),
			'friends.$' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
			'sex' => array('Required' => null, 'Validator:InArray' => array('female', 'male')),
			'partner' => array('Document:User' => null, 'AsReference' => null),
			'_id' => array('Validator:MongoId' => null)
		);
		
		$this->assertEquals($requirements, $this->_bob->getRequirements());
		
		$requirements2 = $requirements = array(
			'$.state' => array('Required' => null),
			'$.suburb' => array('Required' => null),
			'$.postCode' => array('Required' => null),
		);
		
		$this->assertEquals($requirements2, $this->_bob->getRequirements('addresses.'));
	}
	
	public function testHasRequirement()
	{
		$this->assertTrue($this->_bob->hasRequirement('name', 'Required'));
		$this->assertEquals('My_ShantyMongo_Name', $this->_bob->hasRequirement('name', 'Document'));
		$this->assertEquals('My_ShantyMongo_Users', $this->_bob->hasRequirement('friends', 'DocumentSet'));
		
		$this->assertFalse($this->_bob->hasRequirement('age', 'Required'));
		$this->assertFalse($this->_bob->hasRequirement('name', 'Validator:EmailAddress'));
	}
}