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
	public function __construct()
	{
		Shanty_Mongo::init();
		
		return parent::__construct();
	}
}