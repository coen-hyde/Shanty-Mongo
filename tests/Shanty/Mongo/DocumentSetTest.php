<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestSetup.php';

require_once 'PHPUnit/Framework.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/DocumentSet.php';
 
class Shanty_Mongo_DocumentSetTest extends Shanty_Mongo_TestSetup
{
	public function setUp()
	{
		parent::setUp();
		
		$this->_bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->_article = My_ShantyMongo_Article::find('4c04516f1f5f5e21361e3ac1');
	}
	
	public function testGetPropertyKeys()
	{
		$this->assertEquals(array(0, 1), $this->_bob->addresses->getPropertyKeys());
		$this->_bob->addresses[0] = null;
		
		$address = new Shanty_Mongo_Document();
		$address->street = '16 Park Rd';
		$this->_bob->addresses[] = $address;
		
		$this->assertEquals(array(1, 2), $this->_bob->addresses->getPropertyKeys());
	}
	
	public function testGetProperty()
	{
		// Make sure the DocumentSet is sound
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->addresses);
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_bob->addresses));
		$this->assertEquals(2, count($this->_bob->addresses));
		
		// Test basic get
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->addresses[0]);
		$this->assertEquals('Shanty_Mongo_Document', get_class($this->_bob->addresses[0]));
		$this->assertEquals('19 Hill St', $this->_bob->addresses[0]->street);
		$this->assertEquals('default', $this->_bob->addresses[0]->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->addresses[0]->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->addresses[0]->getConfigAttribute('collection'));
		$this->assertEquals('addresses.0', $this->_bob->addresses[0]->getPathToDocument());
		
		$criteria = $this->_bob->addresses[0]->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $criteria['_id']->__toString());
		
		$this->assertFalse($this->_bob->addresses[0]->isNewDocument());
		$this->assertTrue($this->_bob->addresses[0]->getConfigAttribute('parentIsDocumentSet'));
		
		// Test non existing index
		$this->assertNull($this->_bob->addresses[404]);
		
		// Test known references
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->friends[1]);
		$this->assertEquals('My_ShantyMongo_ArtStudent', get_class($this->_bob->friends[1]));

		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_article->contributors);
		$this->assertEquals('My_ShantyMongo_Users', get_class($this->_article->contributors));
		$this->assertEquals(2, count($this->_article->contributors));
		
		$user = $this->_article->contributors[0];
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $user);
		$this->assertEquals('My_ShantyMongo_Student', get_class($user));
		$this->assertEquals('Cherry Jones', $user->name->full());
		
		$this->assertEquals('default', $user->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $user->getConfigAttribute('db'));
		$this->assertEquals('user', $user->getConfigAttribute('collection'));
		$this->assertEquals('', $user->getPathToDocument());
		
		$criteria = $user->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $criteria['_id']->__toString());
		
		// Test broken reference
		$this->assertNull($this->_bob->friends[2]);
		
		// Test unknown references
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_article->relatedArticles);
		$this->assertEquals('Shanty_Mongo_DocumentSet', get_class($this->_article->relatedArticles));
		$this->assertEquals(1, count($this->_article->relatedArticles));
		
		$article = $this->_article->relatedArticles[0];
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $article);
		$this->assertEquals('My_ShantyMongo_Article', get_class($article));
		$this->assertEquals('How to use Bend Space and Time', $article->title);
	}
	
	public function testGetPropertyNewDocument()
	{
		$address = $this->_bob->addresses->new();
		$address->street = '45 Burrow St';
		$address->suburb = 'The Shire';
		$address->state = 'Middle Earth';
		$address->postcode = '21342';
		
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $address);
		$this->assertEquals('Shanty_Mongo_Document', get_class($address));
		$this->assertTrue($address->isNewDocument());
		$this->assertFalse($address->hasId());
		$this->assertTrue($address->getConfigAttribute('parentIsDocumentSet'));
		
		$this->assertEquals('default', $address->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $address->getConfigAttribute('db'));
		$this->assertEquals('user', $address->getConfigAttribute('collection'));
		$this->assertEquals('addresses', $address->getPathToDocument());
		
		$criteria = $address->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $criteria['_id']->__toString());
		
		$address->save();
		
		$bob = My_ShantyMongo_User::find('4c04516a1f5f5e21361e3ab0');
		$this->assertEquals(3, count($bob->addresses));
		$this->assertEquals('45 Burrow St', $bob->addresses[2]->street);
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testGetPropertyNewDocumentException()
	{
		$user = $this->_article->contributors->new();
	}
	
	public function testSetProperty()
	{
		// Unset and existing document
		$this->_bob->addresses[0] = null;
		$this->assertEquals(null, $this->_bob->addresses[0]);
		
		// Test adding new documents into document set
		$address = new Shanty_Mongo_Document();
		$address->street = '16 Park Rd';
		$objStorage = new SplObjectStorage();
		$objStorage->attach($address);
		$this->_bob->addresses[2] = $address;
		$this->assertTrue($objStorage->contains($this->_bob->addresses[2]));
		$this->assertEquals('default', $this->_bob->addresses[2]->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->addresses[2]->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->addresses[2]->getConfigAttribute('collection'));
		$this->assertEquals('addresses.2', $this->_bob->addresses[2]->getPathToDocument());
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'street' => array('Required' => null),
			'state' => array('Required' => null),
			'suburb' => array('Required' => null),
			'postcode' => array('Required' => null),
		);
		
		$this->assertEquals($requirements, $this->_bob->addresses[2]->getRequirements());
		
		$this->assertEquals(array(1, 2), $this->_bob->addresses->getPropertyKeys());
		
		// Test adding documents into an unknown index
		$address = new Shanty_Mongo_Document();
		$address->street = 'testing';
		$objStorage = new SplObjectStorage();
		$objStorage->attach($address);
		$this->_bob->addresses[] = $address;
		$this->assertTrue($objStorage->contains($this->_bob->addresses[3]));
		$this->assertEquals('default', $this->_bob->addresses[3]->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_bob->addresses[3]->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_bob->addresses[3]->getConfigAttribute('collection'));
		$this->assertEquals('addresses.3', $this->_bob->addresses[3]->getPathToDocument());
		
		$criteria = $this->_bob->addresses[3]->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $criteria['_id']->__toString());
		
		// Test adding old documents to document set
		$objStorage = new SplObjectStorage();
		$objStorage->attach($this->_bob->addresses[1]);
		$this->_bob->addresses[4] = $this->_bob->addresses[1];
		$this->assertFalse($objStorage->contains($this->_bob->addresses[4]));
		$this->assertEquals('addresses.4', $this->_bob->addresses[4]->getPathToDocument());
		$this->assertFalse($this->_bob->addresses[4]->isNewDocument());
		$this->assertEquals('Springfield', $this->_bob->addresses[4]->suburb);
		
		// Test adding documents that will be saved as references
		$objStorage = new SplObjectStorage();
		$objStorage->attach($this->_bob->friends[0]);
		$this->_article->contributors[5] = $this->_bob->friends[0];
		$this->assertFalse($this->_article->contributors[5]->isNewDocument());
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_article->contributors[5]);
		$this->assertEquals('My_ShantyMongo_Student', get_class($this->_article->contributors[5]));
		$this->assertTrue($objStorage->contains($this->_article->contributors[5]));
		$this->assertEquals('default', $this->_article->contributors[5]->getConfigAttribute('connectionGroup'));
		$this->assertEquals(TESTS_SHANTY_MONGO_DB, $this->_article->contributors[5]->getConfigAttribute('db'));
		$this->assertEquals('user', $this->_article->contributors[5]->getConfigAttribute('collection'));
		$this->assertEquals('', $this->_article->contributors[5]->getPathToDocument());
		
		$requirements = array(
			'_id' => array('Validator:MongoId' => null),
			'_type' => array('Array' => null),
			'name' => array('Document:My_ShantyMongo_Name' => null, 'Required' => null),
			'concession' => array('Required' => null),
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
		
		$this->assertEquals($requirements, $this->_article->contributors[5]->getRequirements());
		
		$criteria = $this->_article->contributors[5]->getCriteria();
		$this->assertTrue(isset($criteria['_id']));
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $criteria['_id']->__toString());
		
		// Test displacing an unknown reference
		$article = $this->_article->relatedArticles[0];
		$this->assertTrue($this->_article->relatedArticles->isReference($article));
		
		$newArticle = new My_ShantyMongo_Article();
		$newArticle->title = 'Boo';
		$this->_article->relatedArticles[0] = $article;
		$this->assertFalse($this->_article->relatedArticles->isReference($article));
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyNonNumericIndexException()
	{
		$this->_bob->addresses['non numeric index'] = new Shanty_Mongo_Document();
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyNonDocumentException()
	{
		$this->_bob->addresses[5] = 'Not a document';
	}
	
	/**
     * @expectedException Shanty_Mongo_Exception
     */
	public function testSetPropertyRequirementsException()
	{
		$address = new Shanty_Mongo_Document();
		$address->street = '234 ';
		$this->_bob->addresses[] = $address;
		$address->export();
	}
	
	public function testExport()
	{
		$this->_bob->addresses[0] = null;
		
		$address = new Shanty_Mongo_Document();
		$address->street = '155 Long St';
		$address->suburb = 'Big Place';
		$address->state = 'QLD';
		$address->postcode = '4000';
		$address->country = 'Australia';
		
		$this->_bob->addresses[] = $address;
		
		$exportData = array(
			null,
			array(
				'street' => '742 Evergreen Terrace',
				'suburb' => 'Springfield',
				'state' => 'Nevada',
				'postcode' => '89002',
				'country' => 'USA'
			),
			array(
				'street' => '155 Long St',
				'suburb' => 'Big Place',
				'state' => 'QLD',
				'postcode' => '4000',
				'country' => 'Australia'
			)
		);
		
		$this->assertEquals($exportData, $this->_bob->addresses->export());
	}
	
	public function testExportReferences()
	{
		// Pull all reference doc
		foreach ($this->_bob->friends as $friend) {
			
		}
		
		// Bob is going to become a friend of himself
		$this->_bob->friends[] = $this->_bob;
		
		$exportData = $this->_bob->friends->export();
		$this->assertEquals(3, count($exportData));
		
		$this->assertTrue(MongoDBRef::isRef($exportData[0]));
		$this->assertEquals('4c04516f1f5f5e21361e3ab1', $exportData[0]['$id']->__toString());
		
		$this->assertTrue(MongoDBRef::isRef($exportData[1]));
		$this->assertEquals('4c0451791f5f5e21361e3ab2', $exportData[1]['$id']->__toString());
		
		$this->assertTrue(MongoDBRef::isRef($exportData[2]));
		$this->assertEquals('4c04516a1f5f5e21361e3ab0', $exportData[2]['$id']->__toString());
	}
	
	public function testAddDocument()
	{
		$address = new Shanty_Mongo_Document();
		$address->street = '155 Long St';
		$address->suburb = 'Big Place';
		$address->state = 'QLD';
		$address->postcode = '4000';
		$address->country = 'Australia';
		
		$objStorage = new SplObjectStorage();
		$objStorage->attach($address);
		$this->_bob->addresses->addDocument($address);
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $this->_bob->addresses[2]);
		$this->assertEquals('Shanty_Mongo_Document', get_class($this->_bob->addresses[2]));
		$this->assertTrue($objStorage->contains($this->_bob->addresses[2]));
	}
	
	public function testPushDocument()
	{
		$address1 = new Shanty_Mongo_Document();
		$address1->street = '155 Long St';
		$address1->suburb = 'Big Place';
		$address1->state = 'QLD';
		$address1->postcode = '4000';
		$address1->country = 'Australia';
		
		$this->_bob->addresses->pushDocument($address1);
		
		$address2 = new Shanty_Mongo_Document();
		$address2->street = '2 Short St';
		$address2->suburb = 'Big Place';
		$address2->state = 'QLD';
		$address2->postcode = '4000';
		$address2->country = 'Australia';
		
		$this->_bob->addresses->pushDocument($address2);
		
		$operations = array(
			'$pushAll' => array(
				'addresses' => array(
					$address1->export(),
					$address2->export()
				)
			)
		);

		$this->assertEquals($operations, $this->_bob->addresses->getOperations());
	}
	
	public function testGetOperations()
	{
		// Test non references
		$this->_bob->addresses[0]->addOperation('$set', 'street', '43 Hole St');
		$this->_bob->addresses[0]->addOperation('$set', 'suburb', 'Ipswich');
		$this->_bob->addresses[1]->addOperation('$set', 'street', '745 Evergreen Terrace');
		
		$operations = array(
			'$set' => array(
				'addresses.0.street' => '43 Hole St',
				'addresses.0.suburb' => 'Ipswich',
				'addresses.1.street' => '745 Evergreen Terrace',
			)
		);
		
		$this->assertEquals($operations, $this->_bob->addresses->getOperations(true));
		
		// Test references
		$this->_article->contributors[0]->addOperation('$set', 'email', 'blabla@domain.com');
		$this->assertEquals(array(), $this->_article->contributors->getOperations(true));
	}
	
	public function testPurgeOperations()
	{
		$this->_bob->addresses[0]->addOperation('$set', 'street', '43 Hole St');
		$this->_bob->addresses[0]->addOperation('$set', 'suburb', 'Ipswich');
		$this->_bob->addresses[1]->addOperation('$set', 'street', '745 Evergreen Terrace');
		$this->_bob->addresses->purgeOperations(true);
		$this->assertEquals(array(), $this->_bob->addresses->getOperations(true));
		
		// Test references
		$this->_article->contributors[0]->addOperation('$set', 'email', 'blabla@domain.com');
		$this->_article->contributors->purgeOperations(true);
		
		$operations = array(
			'$set' => array(
				'email' => 'blabla@domain.com',
			)
		);
		
		$this->assertEquals($operations, $this->_article->contributors[0]->getOperations(true));
	}
	
	public function testMagicCall()
	{
		// Test get new document
		$newAddress = $this->_bob->addresses->new();
		$this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $newAddress);
		$this->assertEquals('Shanty_Mongo_Document', get_class($newAddress));
	}
	
	/**
	 * @expectedException Shanty_Mongo_Exception
	 */
	public function testMagicCallException()
	{
		$this->_bob->addresses->noMethod();
	}
}