<?php
/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Connection extends Mongo
{
	public function __construct($server = null, array $options = array())
	{
		Shanty_Mongo::init();
		
		// Set the server to local host if one was not provided
		if (is_null($server)) $server = '127.0.0.1';
		
		$options['connect'] = false;
		
		return parent::__construct($server, $options);
	}
}