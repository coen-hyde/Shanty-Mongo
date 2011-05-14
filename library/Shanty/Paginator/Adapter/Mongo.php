<?php

/**
 * @see Zend_Paginator_Adapter_Interface
 */
require_once 'Zend/Paginator/Adapter/Interface.php';

/**
 * @category   Shanty
 * @package    Shanty_Paginator
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Stefan Heckler
 */
class Shanty_Paginator_Adapter_Mongo implements Zend_Paginator_Adapter_Interface
{
    /**
     * Cursor
     *
     * @var Shanty_Mongo_Iterator_Cursor
     */
    protected $_cursor = null;

    /**
     * Constructor.
     *
     * @param Shanty_Mongo_Iterator_Cursor $cursor
     */
    public function __construct(Shanty_Mongo_Iterator_Cursor $cursor)
    {
        $this->_cursor = $cursor;
    }

    /**
     * Returns an cursor limited to items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Shanty_Mongo_Iterator_Cursor
     */
	public function getItems($offset, $itemCountPerPage) 
	{
		$cursor = $this->_cursor->skip($offset)->limit($itemCountPerPage);
		return $cursor;
	}

    /**
     * Returns the total number of rows in the cursor.
     *
     * @return integer
     */
    public function count()
    {
        return $this->_cursor->count();
    }
}