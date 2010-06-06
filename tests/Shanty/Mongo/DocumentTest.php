<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/Document.php';
require_once 'Shanty/Mongo/Connection/StackTest.php';
require_once 'Shanty/Mongo/Connection/GroupTest.php';
 
class Shanty_Mongo_DocumentTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		$this->_bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->_cherry = My_ShantyMongo_User::find('4c04516f1f5f5e21361e3ab1');
		$this->_roger = My_ShantyMongo_User::find('4c0451791f5f5e21361e3ab2');
	}
	
	public function testGetHasId()
	{
		$this->assertTrue($this->_bob->hasId());
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->getId());
		$this->assertEquals('MongoId', get_class($this->_bob->getId()));
	}
	
	public function testGetSetHasConfigAttribute()
	{
		$this->assertFalse($this->_bob->hasConfigAttribute('magic'));
		$this->assertNull($this->_bob->getConfigAttribute('magic'));
		
		$this->_bob->setConfigAttribute('magic', 'somevalue');
		
		$this->assertTrue($this->_bob->hasConfigAttribute('magic'));
		$this->assertEquals('somevalue', $this->_bob->getConfigAttribute('magic'));
	}
	
	/**
	 * @depends testGetSetHasConfigAttribute
	 */
	public function testGetSetHasCollection()
	{
		$this->assertTrue($this->_bob->hasCollection());
		$this->assertEquals('user', $this->_bob->getCollection());
		
		$this->_bob->setCollection('staff');
		$this->assertEquals('staff', $this->_bob->getCollection());
		
		$nameDoc = new My_ShantyMongo_Name();
		$this->assertFalse($nameDoc->hasCollection());
		$this->assertNull($nameDoc->getCollection());
	}
	
	public function testGetPathToDocument()
	{
		$this->assertNull($this->_bob->getPathToDocument());
		$this->_bob->setPathToDocument('stange path');
		$this->assertEquals('stange path', $this->_bob->getPathToDocument());
	}
	
	public function testGetPathToProperty()
	{
		$this->assertEquals('name', $this->_bob->getPathToProperty('name'));
		$this->assertEquals('name.first', $this->_bob->name->getPathToProperty('first'));
	}
	
	public function testIsRootDocument()
	{
		$this->assertTrue($this->_bob->isRootDocument());
		$this->assertFalse($this->_bob->name->isRootDocument());
	}
	
	public function hasKey()
	{
		$this->assertTrue($this->_bob->hasKey());
		$this->assertFalse($this->_bob->name->hasKey());
	}
	
	public function testIsParentDocumentSet()
	{
		$this->assertFalse($this->_bob->isParentDocumentSet());
		
		// Butcher an instance so it thinks it's parent is a document set
		$sarah = new My_ShantyMongo_User(null, array('parentIsDocumentSet' => true));
		$this->assertTrue($sarah->isParentDocumentSet());
	}
	
	public function testGetSetHasCriteria()
	{
		$this->assertEquals(array('_id' => new MongoId()), $this->_bob->getCriteria());
		$this->assertFalse($this->_bob->hasCriteria('username'));
		$this->_bob->setCriteria('username', 'bobjones');
		$this->assertTrue($this->_bob->hasCriteria('username'));
		$this->assertEquals('bobjones', $this->_bob->getCriteria('username'));
		$this->assertEquals(array('_id' => new MongoId(), 'username' => 'bobjones'), $this->_bob->getCriteria());
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
			'partner' => array('Document:My_ShantyMongo_User' => null, 'AsReference' => null),
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
		$this->assertEquals('Shanty_Mongo_DocumentSet', $this->_bob->hasRequirement('addresses', 'DocumentSet'));
		
		$this->assertFalse($this->_bob->hasRequirement('age', 'Required'));
		$this->assertFalse($this->_bob->hasRequirement('name', 'Validator:EmailAddress'));
		$this->assertFalse($this->_bob->hasRequirement('sex', 'Document'));
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testHasRequirementException()
	{
		$this->_bob->addRequirement('preferences', 'Document:My_ShantyMongo_Preferences');
		$this->_bob->hasRequirement('preferences', 'Document');
	}
	
	/**
	 * @depends testGetRequirements
	 */
	public function testApplyRequirementsDirty()
	{
		$totalRequirements = array(
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
			'birthday' => array('Required' => null),
			'mobile' => array('Required' => null, 'Validator:Digits' => null)
		);
		
		$this->_bob->applyRequirements(array(
			'birthday' => 'Required',
			'mobile' => array('Required', 'Validator:Digits')
		));
		
		$this->assertEquals($totalRequirements, $this->_bob->getRequirements());
	}
	
	/**
	 * @depends testGetRequirements
	 */
	public function testApplyRequirementsClean()
	{
		$totalRequirements = array(
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
			'birthday' => array('Required' => null),
			'mobile' => array('Required' => null, 'Validator:Digits' => null)
		);
		
		$this->_bob->applyRequirements(array(
			'birthday' => array('Required' => null),
			'mobile' => array('Required' => null, 'Validator:Digits' => null)
		), false);
		
		$this->assertEquals($totalRequirements, $this->_bob->getRequirements());
	}
	
	/**
	 * @depends testHasRequirement
	 */
	public function testAddRequirement()
	{
		$this->assertFalse($this->_bob->hasRequirement('mobile', 'Validator:Digits'));
		$this->_bob->addRequirement('mobile', 'Validator:Digits');
		$this->assertTrue($this->_bob->hasRequirement('mobile', 'Validator:Digits'));
	}
	
	/**
	 * @depends testHasRequirement
	 */
	public function testRemoveRequirement()
	{
		$this->assertTrue($this->_bob->hasRequirement('name', 'Required'));
		$this->_bob->removeRequirement('name', 'Required');
		$this->assertFalse($this->_bob->hasRequirement('name', 'Required'));
	}
	
	public function testGetPropertiesWithRequirement()
	{
		$reqiredProperties = array(
			'name', 
			'email', 
			'sex'
		);
		
		$this->assertEquals($reqiredProperties, $this->_bob->getPropertiesWithRequirement('Required'));
	}
	
	public function testGetValidators()
	{
		$validatorChain = $this->_bob->getValidators('email');
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $validatorChain);
		$this->assertEquals('Zend_Validate', get_class($validatorChain));
		$this->assertTrue($validatorChain->isValid('email@domain.com'));
		$this->assertFalse($validatorChain->isValid('email#domain.com'));
	}
	
	public function testGetFilters()
	{
		$this->_bob->addRequirement('username', 'Filter:StringToUpper');
		
		$filterChain = $this->_bob->getFilters('username');
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $filterChain);
		$this->assertEquals('Zend_Filter', get_class($filterChain));
		$this->assertEquals('BOBJONES', $filterChain->filter('bobjones'));
	}
	
	public function testIsValid()
	{
		$this->assertTrue($this->_bob->isValid('email', 'email@domain.com'));
		$this->assertFalse($this->_bob->isValid('email', 'email#domain.com'));
	}
	
	public function testGetProperty()
	{
		// $bob->email
		$this->assertEquals('bob.jones@domain.com', $this->_bob->getProperty('email'));
		
		// $bob->name->first
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->getProperty('name'));
		$this->assertEquals('My_ShantyMongo_Name', get_class($this->_bob->getProperty('name')));
		$this->assertEquals('Bob', $this->_bob->getProperty('name')->getProperty('first'));
		$this->assertTrue($this->_bob->getProperty('name')->hasCollection());
		$this->assertEquals('name', $this->_bob->getProperty('name')->getPathToDocument());
		$this->assertFalse($this->_bob->getProperty('name')->getConfigAttribute('hasId'));
		$this->assertFalse($this->_bob->getProperty('name')->isNewDocument());
		
		// $bob->partner->name->first
		$cherry = $this->_bob->getProperty('partner');
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_User', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->getProperty('name')->getProperty('first'));
		
		// $bob->addresses[1]->street
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->getProperty('addresses'));
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_bob->getProperty('addresses')));
		$this->assertEquals(2, count($this->_bob->getProperty('addresses')));
		$this->assertEquals('742 Evergreen Terrace', $this->_bob->getProperty('addresses')->getProperty(1)->getProperty('street'));
		
		// Test get on new documents
		$sarah = new My_ShantyMongo_User();
		
		// $sarah->email
		$this->assertNull($sarah->getProperty('email'));
		
		// $sarah->name
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $sarah->getProperty('name'));
		$this->assertEquals('My_ShantyMongo_Name', get_class($sarah->getProperty('name')));
		
		// $sarah->addresses
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $sarah->getProperty('addresses'));
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($sarah->getProperty('addresses')));
		$this->assertEquals(0, count($sarah->getProperty('addresses')));
		
		// Test broken references
//		$this->assertNull($this->_cherry->getProperty('bestFriend'));
		
//		$this->_bob->addRequirement()
	}
	
	public function testSetProperty()
	{
		$this->markTestIncomplete();
	}
	
	public function testHasProperty()
	{
		$this->assertTrue($this->_bob->hasProperty('email'));
		$this->assertFalse($this->_bob->hasProperty('birthday'));
		$this->_bob->email = null;
		$this->assertFalse($this->_bob->hasProperty('email'));
		$this->_bob->email = 'email@domain.com';
		$this->assertTrue($this->_bob->hasProperty('email'));
	}
	
	public function testGetPropertyKeys()
	{
		$properties = array(
			'_id',
			'name',
			'addresses',
			'email',
			'sex',
			'partner',
			'bestFriend'
		);
		
		$this->assertEquals($properties, $this->_bob->getPropertyKeys());
		
		$properties = array(
			'_id',
			'birthday',
			'preferences',
			'name',
			'addresses',
			'sex',
			'partner',
			'bestFriend'
		);
		
		$this->_bob->email = null;
		$this->_bob->birthday = 'November';
		$this->_bob->preferences = new Shanty_Mongo_Document();
		$this->_bob->preferences->color = 'Blue';
		$this->_bob->crimes = new Shanty_Mongo_Document();
		
		$this->assertEquals($properties, $this->_bob->getPropertyKeys());
	}
	
	public function testCreateReference()
	{
		$reference = $this->_bob->createReference();
		$this->assertTrue(MongoDBRef::isRef($reference));
		$this->assertEquals('user', $reference['$ref']);;
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $reference['$id']);
		$this->assertEquals('MongoId', get_class($reference['$id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $reference['$id']->__toString());
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testCreateReferenceNotRootDocumentException()
	{
		$this->_bob->name->createReference();
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testCreateReferenceNoCollectionException()
	{
		$name = new My_ShantyMongo_Name();
		$name->createReference();
	}
	
	
	public function testIsReference()
	{
		$roger = $this->_bob->bestFriend;
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $roger);
		$this->assertEquals('Shanty_Mongo_Document', get_class($roger));
		
		$this->assertTrue($this->_bob->isReference($roger));
	}

	public function testExport()
	{
		$this->markTestIncomplete();
	}
	
	public function testIsNewDocument()
	{
		$this->assertFalse($this->_bob->isNewDocument());
		$this->assertFalse($this->_bob->name->isNewDocument());
		
		$sarah = new My_ShantyMongo_User();
		$this->assertTrue($sarah->isNewDocument());
		$this->assertTrue($sarah->name->isNewDocument());
	}
	
	public function testIsEmpty()
	{
		$this->assertFalse($this->_bob->isEmpty());
		$this->assertFalse($this->_bob->name->isEmpty());
		$this->_bob->name->first = null;
		$this->_bob->name->last = null;
		$this->assertTrue($this->_bob->name->isEmpty());
		$this->_bob->name->first = 'Bob';
		$this->assertFalse($this->_bob->name->isEmpty());
		$this->assertFalse($this->_bob->addresses->isEmpty());
		$this->_bob->addresses[0]->street = null;
		$this->_bob->addresses[0]->suburb = null;
		$this->_bob->addresses[0]->state = null;
		$this->_bob->addresses[0]->postcode = null;
		$this->_bob->addresses[0]->country = null;
		$this->_bob->addresses[1] = null;
		$this->assertTrue($this->_bob->addresses->isEmpty());
		
		$user = new My_ShantyMongo_User();
		$this->assertFalse($user->isEmpty());
		$user->name->first = 'Madeline';
		$this->assertFalse($user->name->isEmpty());
		$this->assertFalse($user->isEmpty());
		$user->name->first = null;
		$this->assertTrue($user->name->isEmpty());
		
	}
	
	public function testMagicGetAndSet()
	{
		$this->assertEquals('bob.jones@domain.com', $this->_bob->email);
		$this->_bob->email = 'newemail@domain.com';
		$this->assertEquals('newemail@domain.com', $this->_bob->email);
	}
	
	public function testMagicIssetAndUnset()
	{
		$this->assertTrue(isset($this->_bob->email));
		unset($this->_bob->email);
		$this->assertFalse(isset($this->_bob->email));
	}
	
	public function testOffsetGetAndSet()
	{
		$this->assertEquals('bob.jones@domain.com', $this->_bob['email']);
		$this->_bob['email'] = 'newemail@domain.com';
		$this->assertEquals('newemail@domain.com', $this->_bob['email']);
	}
	
	public function testOffsetExistsAndUnset()
	{
		$this->assertTrue(isset($this->_bob['email']));
		unset($this->_bob['email']);
		$this->assertFalse(isset($this->_bob['email']));
	}
	
	public function testGetIterator()
	{
		$iterator = $this->_bob->getIterator();
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $iterator);
		$this->assertEquals('Shanty_Mongo_Iterator_Default', get_class($iterator));
	}
	
	public function testOperations()
	{
		$this->assertEquals(array(), $this->_bob->getOperations(true));
		
		$this->assertEquals(array(), $this->_bob->name->getOperations());
		$this->_bob->name->addOperation('$set', 'first', 'Bobster');
		
		$operations = array(
			'$set' => array(
				'name.first' => 'Bobster'
			)
		);
		
		$this->assertEquals($operations, $this->_bob->name->getOperations());
		$this->assertEquals($operations, $this->_bob->getOperations(true));
		
		$this->_bob->addOperation('$inc', 'count', 3);
		$this->_bob->addOperation('$inc', 'age', 1);
		
		$address = array(
			'street' => '2352 Long St',
			'suburb' => 'Brisbane',
			'state' => 'QLD',
			'postcode' => '4000',
			'country' => 'Australia'
		);
		$this->_bob->addOperation('$push', 'addresses', $address);
		
		$operations = array(
			'$inc' => array(
				'count' => 3,
				'age' => 1,
			),
			'$push' => array(
				'addresses' => $address
			),
			'$set' => array(
				'name.first' => 'Bobster'
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations(true));
		$this->_bob->purgeOperations(true);
		$this->assertEquals(array(), $this->_bob->getOperations(true));
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testInvalidOperation()
	{
		$this->_bob->addOperation('invalid operation', 'count', 3);
	}
	
	/**
	 * @depends testOperations
	 */
	public function testIncOperation()
	{
		$this->_bob->inc('count', 3);
		
		$operations = array(
			'$inc' => array(
				'count' => 3
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
	}
	
	/**
	 * @depends testOperations
	 */
	public function testPushOperation()
	{
		$address = array(
			'street' => '2352 Long St',
			'suburb' => 'Brisbane',
			'state' => 'QLD',
			'postcode' => '4000',
			'country' => 'Australia'
		);
		$this->_bob->push('addresses', $address);
		
		$operations = array(
			'$pushAll' => array(
				'addresses' => array($address)
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
		
		$this->_bob->purgeOperations();
		
		$address = new Shanty_Mongo_Document();
		$address->street = '2352 Long St';
		$address->suburb = 'Brisbane';
		$address->state = 'QLD';
		$address->postcode = '4000';
		$address->country = 'Australia';
		
		$this->_bob->push('addresses', $address);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
	}
	
	/**
	 * @depends testOperations
	 */
	public function testPullOperation()
	{
		$this->_bob->pull('tags', 'sexy');
		
		$operations = array(
			'$pullAll' => array(
				'tags' => 'sexy'
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
	}
	
	/**
	 * @depends testOperations
	 */
	public function testAddToSetOperation()
	{
		$this->_bob->addToSet('tags', 'sexy');
		
		$operations = array(
			'$addToSet' => array(
				'tags' => 'sexy'
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
	}
	
	/**
	 * @depends testOperations
	 */
	public function testPopOperation()
	{
		$this->_bob->pop('addresses', 1);
		
		$operations = array(
			'$pop' => array(
				'addresses' => 1
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations());
	}
}