<?php
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

require_once 'PHPUnit/Framework.php';
 
class Shanty_Mongo_TestSetup extends PHPUnit_Framework_TestCase
{
	protected $_runtimeIncludePath = null;
	protected $_filesDir = '';
	
	public function setUp()
	{
		$this->_useMyIncludePath();
	}
	
	public function tearDown()
	{
		$this->_restoreIncludePath();
		
		parent::tearDown();
	}
	
	protected function _useMyIncludePath()
	{
		$this->_runtimeIncludePath = get_include_path();
		set_include_path(dirname(__FILE__) . '/_files/' . PATH_SEPARATOR . $this->_runtimeIncludePath);
	}
	
	protected function _restoreIncludePath()
	{
		set_include_path($this->_runtimeIncludePath);
		$this->_runtimeIncludePath = null;
	}
	
	protected function getFilesDir()
	{
		return __DIR__ . '_files';
	}
}