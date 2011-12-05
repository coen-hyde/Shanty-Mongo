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
		
		$this->_articleRegular = My_ShantyMongo_Article::find('4c04516f1f5f5e21361e3ac1');
		$this->_articleBroken = My_ShantyMongo_Article::find('4c04516f1f5f5e21361e3ac2');
	}

	public function testConstruct()
	{
		$student = new My_ShantyMongo_Student();
		$this->assertTrue($student->isNewDocument());
		$this->assertTrue($student->isRootDocument());
		$this->assertTrue($student->isConnected());
		$this->assertTrue($student->hasKey());
		$this->assertTrue($student->hasId());
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $student->getId());
		$this->assertEquals('MongoId', get_class($student->getId()));
		$this->assertEquals(My_ShantyMongo_Student::getCollectionRequirements(), $student->getRequirements());
		
		$type = array(
			'My_ShantyMongo_Student',
			'My_ShantyMongo_User'
		);
		
		$this->assertEquals($type, $student->getInheritance());
		
		$criteria = $student->getCriteria();
		$this->assertTrue(array_key_exists('_id', $criteria));
		$this->assertEquals($student->getId()->__toString(), $criteria['_id']->__toString());
		
		$name = new My_ShantyMongo_Name();
		$this->assertTrue($name->isNewDocument());
		$this->assertTrue($name->isRootDocument());
		$this->assertFalse($name->isConnected());
		$this->assertFalse($name->hasKey());
		$this->assertFalse($name->hasId());
		$this->assertEquals(array(), $name->getInheritance());
		
		$config = array(
			'new' => false,
			'connectionGroup' => 'default',
			'db' => TESTS_SHANTY_MONGO_DB,
			'collection' => 'user',
			'pathToDocument' => 'name',
			'requirementModifiers' => array(
				'middle' => array('Required' => null)
			)
		);
		
		$name = new My_ShantyMongo_Name(array('first'=>'Jerry'), $config);
		$this->assertFalse($name->isNewDocument());
		$this->assertFalse($name->isRootDocument());
		$this->assertTrue($name->isConnected());
		$this->assertEquals($name->first, 'Jerry');

		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'first' => array('Required' => null),
			'last' => array('Required' => null),
			'middle' => array('Required' => null),
		);
		$this->assertEquals($requirements, $name->getRequirements());

		// Test input data initialisation
		$data = array(
			'name' => array('first'=>'Jerry', 'last' => 'Springer'),
			'addresses' => array(
				array(
					'street' => '35 Sheep Lane',
					'suburb' => 'Sheep Heaven',
					'state' => 'New Zealand',
					'postcode' => '2345',
					'country' => 'New Zealand',
				)
			),
			'friends' => array(
				MongoDBRef::create('user', new MongoId('4c04516f1f5f5e21361e3ab1')),
				MongoDBRef::create('user', new MongoId('4c0451791f5f5e21361e3ab2')),
			),
		);

		$student = new My_ShantyMongo_Student($data);
		$this->assertNotNull($student->name);
		$this->assertEquals('My_ShantyMongo_Name', get_class($student->name));
		$this->assertEquals('Sheep Heaven', $student->addresses[0]->suburb);
		$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($student->friends[1]));

	}
	
	public function testGetHasId()
	{
		$this->assertTrue($this->_bob->hasId());
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->getId());
		$this->assertEquals('MongoId', get_class($this->_bob->getId()));
	}
	
	public function testGetInheritance()
	{
		$type = array(
			'My_ShantyMongo_Teacher',
			'My_ShantyMongo_User'
		);
		
		$this->assertEquals($type, $this->_bob->getInheritance());
	}
	
	public function testGetSetHasConfigAttribute()
	{
		$this->assertFalse($this->_bob->hasConfigAttribute('magic'));
		$this->assertNull($this->_bob->getConfigAttribute('magic'));
		
		$this->_bob->setConfigAttribute('magic', 'somevalue');
		
		$this->assertTrue($this->_bob->hasConfigAttribute('magic'));
		$this->assertEquals('somevalue', $this->_bob->getConfigAttribute('magic'));
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
		$this->assertEquals(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0')), $this->_bob->getCriteria());
		$this->assertFalse($this->_bob->hasCriteria('username'));
		$this->_bob->setCriteria('username', 'bobjones');
		$this->assertTrue($this->_bob->hasCriteria('username'));
		$this->assertEquals('bobjones', $this->_bob->getCriteria('username'));
		$this->assertEquals(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0'), 'username' => 'bobjones'), $this->_bob->getCriteria());
	}
	
	public function test_GetMongoDb()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->_getMongoDb());
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->_getMongoDb()->__toString());
		
		$connection = new Shanty_Mongo_Connection('localhost');
		Shanty_Mongo::addSlave($connection);
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->_getMongoDb(false));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->_getMongoDb(false)->__toString());
	}

	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function test_GetMongoDbException()
	{
		$name = new My_ShantyMongo_Name();
		$name->_getMongoDb();
	}
	
	public function test_GetMongoCollection()
	{
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->_getMongoCollection());
		$this->assertEquals(TESTS_SHANTY_MONGO_DB.'.user', $this->_bob->_getMongoCollection()->__toString());
	}

	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetMongoCollectionException()
	{
		$name = new My_ShantyMongo_Name();
		$name->_getMongoCollection();
	}
	
	public function testGetRequirements()
	{
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
			'faculty' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, $this->_bob->getRequirements());
		
		$requirements2 = $requirements = array(
			'$.street' => array('Required' => null),
			'$.state' => array('Required' => null),
			'$.suburb' => array('Required' => null),
			'$.postcode' => array('Required' => null),
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
	public function testHasRequirementNoClassException()
	{
		$this->_bob->addRequirement('preferences', 'Document:My_ShantyMongo_Preferences');
		$this->_bob->hasRequirement('preferences', 'Document');
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testHasRequirementInvalidDocumentException()
	{
		$this->_bob->addRequirement('preferences', 'Document:My_ShantyMongo_InvalidDocument');
		$this->_bob->hasRequirement('preferences', 'Document');
	}
	
	/**
	 * @depends testGetRequirements
	 */
	public function testApplyRequirementsDirty()
	{
		$totalRequirements = array(
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
			'faculty' => array('Required' => null),
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
			'faculty' => array('Required' => null),
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
			'sex',
			'faculty'
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
		$this->assertTrue($this->_bob->getProperty('name')->isConnected());
		$this->assertEquals('default', $this->_bob->getProperty('name')->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->getProperty('name')->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->getProperty('name')->getConfigAttribute('collection'));
		$this->assertEquals('name', $this->_bob->getProperty('name')->getPathToDocument());
		$this->assertFalse($this->_bob->getProperty('name')->getConfigAttribute('hasId'));
		$this->assertFalse($this->_bob->getProperty('name')->isNewDocument());
		
		// $bob->partner->name->first
		$cherry = $this->_bob->getProperty('partner');
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $cherry);
		$this->assertEquals('My_ShantyMongo_User', get_class($cherry));
		$this->assertEquals('Cherry', $cherry->getProperty('name')->getProperty('first'));
		$this->assertTrue($cherry->isRootDocument());
		$this->assertEquals('default', $cherry->getProperty('name')->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $cherry->getProperty('name')->getConfigAttribute('db'));
		$this->assertEquals('user', $cherry->getProperty('name')->getConfigAttribute('collection'));
		
		// $bob->addresses[1]->street
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->getProperty('addresses'));
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_bob->getProperty('addresses')));
		$this->assertEquals('addresses', $this->_bob->getProperty('addresses')->getPathToDocument());
		$this->assertEquals(2, count($this->_bob->getProperty('addresses')));
		$this->assertEquals('742 Evergreen Terrace', $this->_bob->getProperty('addresses')->getProperty(1)->getProperty('street'));
		$this->assertEquals('default', $this->_bob->getProperty('addresses')->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->getProperty('addresses')->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->getProperty('addresses')->getConfigAttribute('collection'));
		
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
		
		// Test Array's
		$this->assertTrue(is_array($this->_articleRegular->tags));
		$this->assertEquals(array('awesome', 'howto', 'mongodb'), $this->_articleRegular->tags);
		
		// Test broken references
		$this->assertNull($this->_articleBroken->author);
		
	}
	
	/**
	 * @depends testGetProperty
	 */
	public function testSetProperty()
	{
		$this->_bob->email = null;
		$this->assertNull($this->_bob->email);
		
		$objStorage = new SplObjectStorage();
		$objStorage->attach($this->_cherry);
		
		$this->_articleRegular->stakeholder = $this->_cherry;
		$this->assertFalse($objStorage->contains($this->_articleRegular->stakeholder));
		$this->assertFalse($this->_articleRegular->stakeholder->isNewDocument());
		$this->assertEquals($this->_users['cherry'], $this->_articleRegular->stakeholder->export());
		$this->assertEquals('default', $this->_articleRegular->stakeholder->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_articleRegular->stakeholder->getConfigAttribute('db'));
		$this->assertEquals('article', $this->_articleRegular->stakeholder->getConfigAttribute('collection'));
		$this->assertEquals('stakeholder', $this->_articleRegular->stakeholder->getPathToDocument());
		
		$article = new My_ShantyMongo_Article();
		$article->title = 'Mongodb Awesomeness';
		
		$objStorage = new SplObjectStorage();
		$objStorage->attach($article);
		
		$this->_roger->favouriteArticle = $article;
		$this->assertTrue($objStorage->contains($this->_roger->favouriteArticle));
		$this->assertTrue($this->_roger->favouriteArticle->isNewDocument());
		$this->assertEquals($article->export(), $this->_roger->favouriteArticle->export());
		$this->assertEquals('default', $this->_roger->favouriteArticle->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_roger->favouriteArticle->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_roger->favouriteArticle->getConfigAttribute('collection'));
		$this->assertEquals('favouriteArticle', $this->_roger->favouriteArticle->getPathToDocument());
		
		$this->_bob->addRequirement('config', 'Document');
		$this->_bob->addRequirement('config.date', 'Required');
		
		$this->_bob->config = new Shanty_Mongo_Document();
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'date' => array('Required' => null)
		);
		
		$this->assertEquals($requirements, $this->_bob->config->getRequirements());
		
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyPrivateProperty()
	{
		$this->_bob->_private = 'invalid email';
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyInvalidValueException()
	{
		$this->_bob->email = 'invalid email';
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
			'_type',
			'name',
			'addresses',
			'friends',
			'faculty',
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
			'_type',
			'name',
			'addresses',
			'friends',
			'faculty',
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
		$this->assertEquals($this->_users['bob'], $this->_bob->export());
		
		$this->_bob->name->first = 'Bobby';
		$this->_bob->addresses = null;
		$this->_bob->favouriteColour = 'Blue';
		$this->_bob->config = new Shanty_Mongo_Document(); // Empty documents don't get saved

		// Load references into memory to make sure they are exported correctly as references
		$this->_bob->partner;
		$this->_bob->bestFriend;

		$bobRaw = $this->_users['bob'];
		$bobRaw['name']['first'] = 'Bobby';
		unset($bobRaw['addresses']);
		$bobRaw['favouriteColour'] = 'Blue';

		$this->assertEquals($bobRaw, $this->_bob->export());
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testExportRequiredException()
	{
		$this->_bob->email = null;
		$this->_bob->export();
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
	
	public function testSaveBasic()
	{
		$this->_bob->name->first = 'Bobby';
		$this->_bob->addresses = null;
		$this->_bob->save();

		$bobRaw = $this->_users['bob'];
		$bobRaw['name']['first'] = 'Bobby';
		unset($bobRaw['addresses']);

		$this->assertEquals($bobRaw, $this->_userCollection->findOne(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0'))));
	}
	
	public function testSaveWholeDocument()
	{
		$this->_bob->name->last = 'Johnes';
		$this->_bob->addresses = null;
		$this->_bob->save(true);

		$bobRaw = $this->_users['bob'];
		$bobRaw['name']['last'] = 'Johnes';
		unset($bobRaw['addresses']);

		$this->assertEquals($bobRaw, $this->_userCollection->findOne(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0'))));
	}
	
	public function testSaveUnchangedDocument()
	{
		$this->_bob->save();
		$this->assertEquals($this->_users['bob'], $this->_userCollection->findOne(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0'))));
	}
	
	/**
	 * Test newly added documents to a document set
	 */
	public function testSaveChildOfDocumentSet()
	{
		$address = $this->_bob->addresses->new();
		$address->street = '35 Sheep Lane';
		$address->suburb = 'Sheep Heaven';
		$address->state = 'New Zealand';
		$address->postcode = '2345';
		$address->country = 'New Zealand';
		$address->save();

		$bobRaw = $this->_users['bob'];
		$addressRaw = array(
			'street' => '35 Sheep Lane',
			'suburb' => 'Sheep Heaven',
			'state' => 'New Zealand',
			'postcode' => '2345',
			'country' => 'New Zealand',
		);
		$bobRaw['addresses'][] = $addressRaw;
		
		$this->assertEquals($bobRaw, $this->_userCollection->findOne(array('_id' => new MongoId('4c04516a1f5f5e21361e3ab0'))));
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testSaveChildOfDocumentSetSaveException()
	{
		$address = $this->_bob->addresses->new();
		$address->street = '35 Sheep Lane';
		$address->suburb = 'Sheep Heaven';
		$address->state = 'New Zealand';
		$address->postcode = '2345';
		$address->country = 'New Zealand';
		$address->save();
		$address->save(); // Should throw an exception because this document will be locked
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testSaveChildOfDocumentSetDeleteException()
	{
		$address = $this->_bob->addresses->new();
		$address->street = '35 Sheep Lane';
		$address->suburb = 'Sheep Heaven';
		$address->state = 'New Zealand';
		$address->postcode = '2345';
		$address->country = 'New Zealand';
		$address->save();
		$address->delete(); // Should throw an exception because this document will be locked
	}
	
	public function testSaveNewDocument()
	{
		$user = new My_ShantyMongo_User();
		$user->email = 'email@domain.com';
		$user->sex = 'F';
		$user->name->first = 'Madeline';
		$user->name->last = 'Veenstra';
		$user->save();
		
		$userId = $user->getId();
		$userRaw = array(
			'_id' => new MongoId($userId->__toString()),
			'_type' => array(
				'My_ShantyMongo_User'
			),
			'name' => array(
				'first' => 'Madeline',
				'last' => 'Veenstra',
			),
			'email' => 'email@domain.com',
			'sex' => 'F'
		);
		
		$this->assertEquals($userRaw, $this->_userCollection->findOne(array('_id' => new MongoId($userId->__toString()))));
	}

	public function testSaveSafe() {
		$reader = new Mongo('mongodb://' . TESTS_SHANTY_MONGO_CONNECTIONSTRING);
		$readerDB = $reader->{TESTS_SHANTY_MONGO_DB};

		for($nr = 0; $nr < 1000; $nr++) {
			$entry = new My_ShantyMongo_Simple(array('data' => '123'));
			$entry->save();
			$found = $readerDB->simple->findOne(array('_id' => $entry->getId()));
			$this->assertTrue(is_array($found));
			$this->assertEquals($entry->export(), $found);
		}
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSaveNotConnectedException()
	{
		$name = new My_ShantyMongo_Name();
		$name->save();
	}
	
	public function testDelete()
	{
		// Delete subdocument
		$this->_roger->name->delete();
		
		$roger = $this->_userCollection->findOne(array('_id' => new MongoId('4c0451791f5f5e21361e3ab2')));
		
		$rogerData = array(
			'_id' => new MongoId('4c0451791f5f5e21361e3ab2'),
			'_type' => array(
				'My_ShantyMongo_ArtStudent',
				'My_ShantyMongo_Student',
				'My_ShantyMongo_User'
			),
			'concession' => false,
			'email' => 'roger.smith@domain.com',
			'sex' => 'M'
		);
		
		$this->assertEquals($rogerData, $roger);
		
		$this->_roger->delete();
		$this->assertNull($this->_userCollection->findOne(array('_id' => new MongoId('4c0451791f5f5e21361e3ab2'))));
	}
	
	/**
	 * Make sure an exception is thrown if document does not belong to a collection
	 * 
     * @expectedException Shanty_Mongo_Exception
     */
	public function testDeleteException()
	{
		$name = new My_ShantyMongo_Name();
		$name->delete();
	}

	public function testDeleteSafe() {
		$reader = new Mongo('mongodb://' . TESTS_SHANTY_MONGO_CONNECTIONSTRING);
		$readerDB = $reader->{TESTS_SHANTY_MONGO_DB};

		for($nr = 0; $nr < 1000; $nr++) {
			$entry = new My_ShantyMongo_Simple(array('data' => '123'));
			$entry->save();
			$entry->delete();
			$found = $readerDB->simple->findOne(array('_id' => $entry->getId()));
			if (!is_null($found)) {
				print($nr);
				die();
			}
			$this->assertNull($found);
		}
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
		$this->_bob->addOperation('$set', 'email', 'email@domain.com');
		
		$operations = array(
			'$set' => array(
				'name.first' => 'Bobster',
			)
		);
		
		$this->assertEquals($operations, $this->_bob->name->getOperations());
		
		$operations = array(
			'$set' => array(
				'name.first' => 'Bobster',
				'email' => 'email@domain.com'
			)
		);
		
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
				'name.first' => 'Bobster',
				'email' => 'email@domain.com'
			)
		);
		
		$this->assertEquals($operations, $this->_bob->getOperations(true));
		$this->_bob->purgeOperations(true);
		$this->assertEquals(array(), $this->_bob->getOperations(true));
		
		// Test operations of the document it's self.
		$address = array(
			'street' => '2352 Long St',
			'suburb' => 'Brisbane',
			'state' => 'QLD',
			'postcode' => '4000',
			'country' => 'Australia'
		);
		
		$this->_bob->addresses->addOperation('$push', null, $address);
		
		$operations = array(
			'$push' => array(
				'addresses' => $address
			)
		);
		$this->assertEquals($operations, $this->_bob->getOperations(true));
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
	
	/**
	 * @depends testOperations
	 */
	public function testProcessChanges()
	{
		$newRoger = $this->_users['roger'];
		
		$newRoger['name']['first'] = 'Bagger';
		$newRoger['favouriteColour'] = 'Blue';
		unset($newRoger['email']);
		
		$this->_roger->processChanges($newRoger);
		
		$operations = array(
			'$set' => array(
				'name' => array(
					'first' => 'Bagger',
					'last' => 'Smith'
				),
				'favouriteColour' => 'Blue'
			),
			'$unset' => array(
				'email' => 1
			)
		);
		
		$this->assertEquals($operations, $this->_roger->getOperations());
	}
	
	/**
	 * @depends testOperations
	 * @depends testExport
	 */
	public function testProcessChangesNoChanges()
	{
		$this->_bob->processChanges($this->_bob->export());
		$this->assertEquals(array(), $this->_bob->getOperations(true));
	}
	
	/**
	 * @depends testOperations
	 * @depends testExport
	 */
	public function testProcessChangesNoChangesDataInit()
	{
		// Initialise all properties
		foreach ($this->_bob as $property => $value) {
			
		}
		
		$this->_bob->processChanges($this->_bob->export());
		$this->assertEquals(array(), $this->_bob->getOperations(true));
	}
	
	public function testInitHook()
	{
		$this->assertEquals(1, $this->_bob->_hookCounter['init']);
	}
	
	public function testPrePostInsertUpdateSaveHook()
	{
		$user = new My_ShantyMongo_User();
		$user->name->first = 'Stranger';
		$user->name->last = 'Jill';
		$user->email = 'jill@domain.com';
		$user->sex = 'M';
		$user->save();
		
		$user->email = 'jill@address.com';
		$user->save();
		
		$this->assertEquals(1, $user->_hookCounter['preInsert']);
		$this->assertEquals(1, $user->_hookCounter['postInsert']);
		$this->assertEquals(1, $user->_hookCounter['preUpdate']);
		$this->assertEquals(1, $user->_hookCounter['postUpdate']);
		$this->assertEquals(2, $user->_hookCounter['preSave']);
		$this->assertEquals(2, $user->_hookCounter['postSave']);
	}
	
	/**
	 * Test for issue https://github.com/coen-hyde/Shanty-Mongo/issues/50
	 *
	 * @return void
	 * @author Tom Holder
	 **/
	public function testConcreteClassFromArray()
	{
		$data = array(
	       'title' => '101 reasons mongo is the bomb digidy',
	       'relatedArticles' => array(
	           array(
				   'title' => '102 reasons mongo is the bomb digidy',
	               'relatedArticles' => array(
						array(
							'title' => '103 reasons mongo is the bomb digidy',
						)
				   )
	           )
	       )
	    );

	    $article = new My_ShantyMongo_Article($data);

        $this->assertInstanceOf('Shanty_Mongo_DocumentSet', $article->relatedArticles);
        $this->assertInstanceOf('My_ShantyMongo_Article', $article->relatedArticles[0]);
	    $this->assertEquals('102 reasons mongo is the bomb digidy', $article->relatedArticles[0]->title);
	    $this->assertEquals('103 reasons mongo is the bomb digidy', $article->relatedArticles[0]->relatedArticles[0]->title);

	    $exportedData = $article->export();
	    $this->assertEquals('102 reasons mongo is the bomb digidy', $exportedData['relatedArticles'][0]['title']);
	}
}