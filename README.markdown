Shanty Mongo
============

Summary
-------

Shanty Mongo is a prototype mongodb adapter for the Zend Framework. It's intention is to make working with mongodb documents as natural and as simple as possible. In particular allowing embeded documents to also have custom document classes.

Requirements
------------

- PHP 5.3 or greater
- Zend Framework 1.10.0 or greater
- Mongodb 1.3 or greater

Features
--------

- ORM
- Simple and flexible
- Partial updates. Only changed data is sent back to the server. Also you can save or delete embeded documents individually.
- Support for references (lazy loaded)
- Optional schema enforcement: Validation and Filtering on properties
- Embeded documents/documentsets can have custom document classes like the root document

How to Use
----------

### Initialise a connection and set it as a default connection

	$connection = new Shanty_Mongo_Connection('localhost');
	Shanty_Mongo::setDefaultConnection($connection);
	
### Define a document/collection

	class User extends Shanty_Mongo_Document 
	{
		protected static $_collectionName = 'user';
	}
	
### Find a document

	$user = User::find($id);
	
### Create a new document

	$user = new User();
	$user->name = 'Bob';
	$user->save();

### Adding requirements

	class User extends Shanty_Mongo_Document 
	{
		protected static $_collectionName = 'user';
		
		protected $_requirements = array(
			'name' => 'NotEmpty',
			'email' => array('NotEmpty', 'EmailAddress'),
		);
	}

Here we have enforced that both the properties 'name' and 'email' must not be empty when this document is saved. We have also stated that the 'email' property must be an email address. When an attempt to set the 'email' property is made, the value will be run through the validator Zend_Validate_EmailAddress. If it fails validation an exception will be thrown. If you wanted to determine if an email address is valid without throwing an exception call $user->isValid('email', 'invalid@email#address.com');

There are many different available requirements, including Zends validators and filters.

### Creating embedded documents

Say we wanted to also store the users last name. We could have nameFirst and nameLast properties on the document but in the spirit of documents databases we'll make the property 'name' an embedded document with the properties first and last.

	$user = new User();
	$user->name = new Shanty_Mongo_Document();
	$user->name->first = 'Bob';
	$user->name->last = 'Jane';
	$user->save();

Since we know all users must have a first and last name lets enforce it

	class User extends Shanty_Mongo_Document 
	{
		protected static $_collectionName = 'user';
		
		protected $_requirements = array(
			'name' => array('Document', 'NotEmpty'),
			'name.first' => 'NotEmpty',
			'name.last' => 'NotEmpty',
			'email' => array('NotEmpty', 'EmailAddress'),
		);
	}

Notice how i've given the property 'name' the requirement of 'Document'? Now we do not have to initialise a new document when we set a users name. The name document is lazy loaded the first time we try to access it.

	$user = new User();
	$user->name->first = 'Bob';
	$user->name->last = 'Jane';
	$user->save();

### Saving embedded documents

A nice feature is the ability to save embedded documents independently. eg.

	$user = User::find($id);
	$user->name->last = 'Tmart';
	$user->name->save();
	
The above example may be a bit pointless but as your documents grow it will feel 'right' to call save on the document you are changing. It's also handy for when you want to pass embedded document around your application without having to remember where they came from.

No matter where save is called only the changes for that document and all it's children are sent to the database.

### Custom embedded document classes.

Now that we have stored the users first and last names, more than likely will will want to display the users full name. Instead of concatenating the users first and last name every time, we can make 'name' a custom document with a full() method. 

First we'll define the name document

	class Name extends Shanty_Mongo_Document
	{
		protected $_requirements = array(
			'first' => 'NotEmpty',
			'last' => 'NotEmpty',
		);
		
		public function full()
		{
			return $this->first.' '.$this->last;
		}
	}
	
Next we'll tell the user document to use our new document

	class User extends Shanty_Mongo_Document 
	{
		protected static $_collectionName = 'user';
		
		protected $_requirements = array(
			'name' => array('Document:Name', 'NotEmpty'),
			'email' => array('NotEmpty', 'EmailAddress'),
		);
	}

Now lets use our new document

	$user = User::find($id);
	
	// prints 'Bob Jane'
	print($user->name->full()); 
	
	// You could also add a __toString() method and do something like this
	print($user->name);
	
### DocumentSets

Document sets are actually documents themselves but designed to handle a set of other documents. Think of DocumentSets as an array with extras. You may want to use a DocumentSet to store a list of friends or addresses.

Lets store a list of addresses against a user. First we must inform the User document of our new requirements
	
	class User extends Shanty_Mongo_Document 
	{
		protected static $_collectionName = 'user';
		
		protected $_requirements = array(
			'name' => array('Document:Name', 'NotEmpty'),
			'email' => array('NotEmpty', 'EmailAddress'),
			'addresses' => 'DocumentSet',
			'addresses.$.street' => 'NotEmpty',
			'addresses.$.suburb' => 'NotEmpty',
			'addresses.$.state' => 'NotEmpty',
			'addresses.$.postCode' => 'NotEmpty'
		);
	}

First thing you are probably noticing is the $. The $ is a mask for the array position of any document in the set. Requirements specified against the $ will be applied to all elements. In the above example we are enforcing that all document added to the 'addresses' document set have a bunch of properties.

There are few different ways you can use DocumentSets. I'll start with the most common usage.

	$user = User::find($id);
	
	$address = $user->addresses->new();
	$address->street = '88 Hill St';
	$address->suburb = 'Brisbane';
	$address->state = 'QLD';
	$address->postCode = '4000';
	$address->save();
	
There is a bit of magic going on here. First we create a new address. The new method on a DocumentSet returns a new document, by default it will be a Shanty_Mongo_Document. We do our business then save. Save will do a $push operation on $user->addresses with our new document. This is in my opinion the ideal way to add new elements to a document set. Because we a doing a $push operation we do not run the risk of a confict on indexes

We could have also added the new document to the document set like this

	$user = User::find($id);
	
	$address = $user->addresses->new();
	$address->street = '88 Hill St';
	$address->suburb = 'Brisbane';
	$address->state = 'QLD';
	$address->postCode = '4000';
	
	// add address to addresses
	$user->addresses->addDocument(address);
	
	// Is the same as
	//$user->addresses[] = address;
	
	// Or we could have specified the index directly if we really knew what we were doing
	// $user->addresses[4] = address;
	
	$user->addresses->save();

This method may be prefered in certain circumstances

### Fetching multiple documents

We can fetch multiple documents by calling fetchAll. FetchAll will return a Shanty_Mongo_Iterator_Cursor that has all the functionality of MongoCursor

Find all users and print their names

	$users = User::fetchAll();
	
	foreach ($users as $user) {
		print($user->name->full()."<br />\n");
	}
	
fetchAll also accepts queries.

Find all users with the first name Bob

	$users = User::fetchAll(array('name.first' => 'Bob'));
	
### Deleting documents

To delete a document simply call the method delete(). You can call delete() on root documents or embedded documents. eg

	$user = User::find($id);
	
	// Delete the name document
	$user->name->delete();
	
	// Delete the entire document
	$user->delete();
	
### Operations

Operations are queued until a document is saved.

Lets increment a users post count by one

	$user = User::find($id);
	
	$user->inc('posts', 1);
	$user->save();
	
	// Is the same as
	//$user->addOperation('$inc', 'posts', 1);
	//$user->save();

Contact
-------

coen.hyde at gmail dot com