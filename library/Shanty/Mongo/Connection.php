<?php
/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @subpackage Connection
 * @copyright  Shanty Tech Pty Ltd
 * @author     Coen Hyde
 */
class Shanty_Mongo_Connection extends Mongo
{
	public function __construct()
	{
		Shanty_Mongo::init();
		
		return parent::__construct();
	}
}