Shanty Mongo
============

Summary
-------

Shanty Mongo is a prototype mongodb adapter for the Zend Framework. It's intention is to make working with mongodb documents as natural and as simple as possible. In particular allowing embedded documents to also have custom document classes.

Requirements
------------

- PHP 5.3 or greater
- Zend Framework 1.10.0 or greater
- Mongodb 1.3 or greater
- Mongo extension from pecl

Features
--------

- ORM
- Simple and flexible
- Partial updates. Only changed data is sent back to the server. Also you can save or delete embeded documents individually.
- Support for references (lazy loaded)
- Support for inheritance
- Optional schema enforcement: Validation and Filtering on properties
- Embeded documents/documentsets can have custom document classes like the root document

How to Use
----------

### Initialisation

Use Zend's autoloader and add the library folder to your include path

### Connections

If you are connecting to localhost without any authentication then no need to worry about connections any further. Shanty Mongo will connect automatically on the first request if no connections have previously been added.

-Call Model_My_ClassName::getServerInfo() to get an array of information about the server you are connecting to
-Call Model_My_ClassName::getMasterServerInfo() || Model_My_ClassName::getWriteServerInfo() to get an array of information about the primary write server you are connecting to
-Call Model_My_ClassName::getSlaveServerInfo() || Model_My_ClassName::getReadServerInfo() to get an array of information about the primary read server you are connecting to

#### Advanced connections

For information on how to configure master/slave setups, weighted connections and multiple connection goups see the [wiki](http://wiki.github.com/coen-hyde/Shanty-Mongo/connections)

### Define a document/collection

To define a document and the collection that the document will be saved to, extend Shanty_Mongo_Document and set the static properties $\_db and $\_collection.

	class User extends Shanty_Mongo_Document 
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
	}

### Create a new document

	$user = new User();
	$user->name = 'Bob';
	$user->save();

Or you can pass an array of data to the constructor as so

	$data = array(
	    'name' => 'Bob'
	);

	$user = new User($data);
	$user->save();

Or you can call an insert statically from your code

	User::insert(
        array(
            'name' => 'Bob'
        )
	);

### Find a document

Using find($id)

	$user = User::find(
	    $id
    );
    // $id can either be a string representation of the document id or an instance of MongoId.

Using one($query = array(), $fields = array())

    $user = User::one(
        array(
            '_id' => new MongoId($id)
        )
    );

Using fetchOne($query = array(), $fields = array())

    $user = User::fetchOne(
        array(
            '_id' => new MongoId($id)
        )
    );

### Fetching multiple documents

We can fetch multiple documents by calling all. All will return a Shanty_Mongo_Iterator_Cursor that has all the functionality of MongoCursor

Find all users and print their names

	$users = User::all();

	foreach ($users as $user) {
		print($user->name->full()."<br />\n");
	}

**All also accepts queries.**

Find all users with the first name Bob

	$users = User::all(
	    array(
	        'name.first' => 'Bob'
        )
    );

Just as with finding a single document you can limit the fields that Shanty Mongo will pull down.

    $users = User::all(
        array(
            'name.first' => 'Bob'
        ),
        array(
            'name' => 1, 'email' => 1
        )
    );

This will return only the name and email address for all users.

### Adding requirements

There are 3 types of requirements. Validators, filters and special. 

#### Validators

To use a validator add a requirement with the prefix 'Validator:' followed by the name of the validator. Please see the Zend reference guide for the list of [Zend validators](http://framework.zend.com/manual/en/zend.validate.set.html). I addition to the validators supported by Zend, Shanty Mongo supports the following validators:

- Validator:Array

- Validator:MongoId

#### Filters

To use a filter add a requirement with the prefix 'Filter:' followed by the name of the filter. Please see the Zend reference guide for the list of [Zend filters](http://framework.zend.com/manual/en/zend.filter.set.html). 

#### Requirements with special meaning or behaviour

- Document:{ClassName}  
  Validates a property is of type ClassName and lazy loads an instance of the document on first access. If no ClassName is provided then Shanty_Mongo_Document is assumed. eg 'Document:User' or without a class name 'Document'.

- DocumentSet:{ClassName}  
  Validates a property is of type ClassName and lazy loads an instance of the documentset on first access. If no ClassName is provided then Shanty_Mongo_DocumentSet is assumed. eg 'DocumentSet:Posts' or without a class name 'DocumentSet'.

- Required  
  Ensures that a property exists. Unlike most validators that run when a property is set, Required is run when a document is saved.

- AsReference  
  Will save a document as a reference

#### Lets put some of them to use

	class User extends Shanty_Mongo_Document 
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected static $_requirements = array(
			'name' => 'Required',
			'email' => array('Required', 'Validator:EmailAddress'),
			'friends' => 'DocumentSet',
			'friends.$' => array('Document:User', 'AsReference')
		);
	}

There is a lot going on here so i don't expect you to understand what is happening just yet. 

Even though there are 4 keys in the requirement list we are actually only specifying requirements for 3 properties. The last two 'friends' and 'friends.$' both refer to the 'friends' property. 

We have enforced that both the properties 'name' and 'email' are required while 'friends' is optional. We have also stated that the 'email' property must be an email address. When an attempt to set the 'email' property is made, the value will be run through the validator Zend_Validate_EmailAddress. If it fails validation an exception will be thrown. If you wanted to determine if an email address is valid without throwing an exception call $user->isValid('email', 'invalid@email#address.com');

The property 'friends' is a document set and all it's elements are documents of type 'User'. When this document set is saved all the 'User' documents will be saved as references. More on document sets later.

#### Validators and Filters with options

Some validators and filters have additional options that need to be passed to it's constructor. This can be achieve by setting the requirement as the key and the options as the value. As a demonstration we'll add a sex property on the user object and use the InArray validator. 

	class User extends Shanty_Mongo_Document 
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected static $_requirements = array(
			'name' => 'Required',
			'email' => array('Required', 'Validator:EmailAddress'),
			'friends' => 'DocumentSet',
			'friends.$' => array('Document:User', 'AsReference'),
			'sex' => array('Validator:InArray' => array('female', 'male');
		);
	}

### Creating embedded documents

Say we wanted to also store the users last name. We could have nameFirst and nameLast properties on the document but in the spirit of document databases we'll make the property 'name' an embedded document with the properties first and last.

	$user = new User();
	$user->name = new Shanty_Mongo_Document();
	$user->name->first = 'Bob';
	$user->name->last = 'Jane';
	$user->save();

Since we know all users must have a first and last name lets enforce it

	class User extends Shanty_Mongo_Document 
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected static $_requirements = array(
			'name' => array('Document', 'Required'),
			'name.first' => 'Required',
			'name.last' => 'Required',
			'email' => array('Required', 'Validator:EmailAddress'),
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
		protected static $_requirements = array(
			'first' => 'Required',
			'last' => 'Required',
		);
		
		public function full()
		{
			return $this->first.' '.$this->last;
		}
	}
	
Next we'll tell the user document to use our new document

	class User extends Shanty_Mongo_Document 
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected static $_requirements = array(
			'name' => array('Document:Name', 'Required'),
			'email' => array('Required', 'Validator:EmailAddress'),
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
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected static $_requirements = array(
			'name' => array('Document:Name', 'Required'),
			'email' => array('Required', 'Validator:EmailAddress'),
			'addresses' => 'DocumentSet',
			'addresses.$.street' => 'Required',
			'addresses.$.suburb' => 'Required',
			'addresses.$.state' => 'Required',
			'addresses.$.postCode' => 'Required'
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

This method may be preferred in certain circumstances

### Using Skip, Limit, Sort etc

Since the shanty mongo cursor returned by the all method is a subclass of MongoCursor you have all the functionality that is usually available to you as if you were querying mongodb directy. eg

    $users = User::all()->skip(10)->limit(5);

This will skip the first 10 users and limit the result set to 5 users. Even though it may appear as though we are fetching all the users then skipping and limiting the result set on the php end, this is not the case. The nice thing about the way the Mongo implements cursors is that no results are fetched from the database until the method getNext is called, directly or indirectly. This means that the above skip and limit will only fetch 5 users from the database.

### Deleting documents

To delete a document simply call the method delete(). You can call delete() on root documents or embedded documents. eg

	$user = User::find($id);
	
	// Delete the name document
	$user->name->delete();
	
	// Delete the entire document
	$user->delete();

### Deleting from a collection

Maybe you just want to delete all users with the first name John without fetching and initialising all the John documents

    User::remove(array('name.first' => 'John'));

If you would like that operation to be safe remember to pass the safe flag

    User::remove(array('name.first' => 'John'), array('safe' => true));

### Batch Inserting

Sometimes you just want to save a whole bunch of stuff to the database without the extra overhead of initialising documents.

    $users = array(
        array(
            'name' => array(
                'first' => 'John',
                'last' => 'Mackison'
            ),
            'email' => 'john@mackison.com'
        ),
        array(
            'name' => array(
                'first' => 'Joan',
                'last' => 'Mackison'
            ),
            'email' => 'joan@mackison.com'
        )
    );

    User::insertBatch($users);

This will insert two users into the user collection. A word or warning; batch inserting bypasses all validation and filtering.

### Operations

Operations are queued until a document is saved.

Lets increment a users post count by one

	$user = User::find($id);
	
	$user->inc('posts', 1);
	$user->save();
	
	// Is the same as
	$user->addOperation('$inc', 'posts', 1);
	$user->save();
	
Operations also work fine on subdocuments 

	$user->name->addOperation('$set', 'first', 'Bob);
	$user->name->save();
	
	// This would also work
	$user->save();

### Inheritance

As of 0.3 Shanty Mongo supports inheritance

	Class User extends Shanty_Mongo_Document
	{
		protected static $_db = 'lms';
		protected static $_collection = 'user';
		protected static $_requirements = array(
			'name' => array('Document:Name', 'Required'),
			'email' => 'Validator:EmailAddress'
		);
	}
	
	Class Student extends User
	{
		protected static $_requirements = array(
			'email' => 'Required',
			'classes' => 'DocumentSet'
		);
	}
	
	Class SchoolCaptain extends Student
	{
		protected static $_requirements = array(
			'obligations' => 'Array'
		);
	}
	
In the above User, Student and SchoolCaptain will be saved in the user collection. Even though it looks like the requirements in User are being over-ridden by the requirements in Student and SchoolCaptain but they are not. Using some static magic they are actually merged. 

So the effective requirements for SchoolCaptain would be:

	array(
		'name' => array('Document:Name', 'Required'),
		'email' => array('Required', 'Validator:EmailAddress'),
		'classes' => 'DocumentSet',
		'obligations' => 'Array',
	);

#### Querying for subclasses is easy

	$users = User::all(); // Returns all Users
	
	foreach ($users as $user) {
		print(get_class($user)); // Will print either User, Student or SchoolCaptain
	}
	
	Student::all(array('name.first' => 'Bob')); // Returns only Students with the first name of 'Bob'
	SchoolCaptain::all(); // Returns only school captains
	
Before you jump in and use inheritance all over the place just be aware that searching subclasses will query the attribute '_type' so be sure to index it for use in production.

	$users = User::all(); // No lookup on '_type'
	$students = Student::all(); // A lookup on '_type' is used
	$schoolCaptains = SchoolCaptain::all(); // A lookup on '_type' is used

### Hooks

The following hooks are available:

##### init()

Executed after the constructor has finished

##### preInsert()

Executed before saving a new document

##### postInsert()

Executed after saving a new document

##### preUpdate()

Executed before saving an existing document

##### postUpdate()

Executed after saving an existing document

##### preSave()

Executed before saving a document

##### postSave()

Executed after saving a document

##### preDelete()

Executed before deleting a document

##### postDelete()

Executed after deleting a document

#### Using the Hooks

To use one of the above hooks simply define a protected method in you document with the name of the hook eg.

	Class User extends Shanty_Mongo_Document
	{
		protected static $_db = 'forum';
		protected static $_collection = 'user';
		
		protected function init()
		{
			// Do stuff on initialising document
		}
	}

Special thanks to
-----------------

[stunti](http://github.com/stunti) for bug fixes
[sh](http://github.com/sh) for Shanty_Paginator

Mongoid for inspiration
 
Contact
-------

coen.hyde at gmail dot com
