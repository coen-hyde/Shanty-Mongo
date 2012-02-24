<?php
require_once 'Shanty/Mongo/Exception.php';
require_once 'Shanty/Mongo/Collection.php';
require_once 'Shanty/Mongo/Iterator/Default.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Coen Hyde
 */
class Shanty_Mongo_Profiler extends Zend_Db_Profiler
{
    public function startQuery($config, $queryType = null)
    {
        $string = array();
        foreach($config as $key => $item)
        {
            switch($key)
            {
                case 'database':
                    $string[] = 'Database: '.$item;
                    break;
                case 'collection':
                    $string[] = 'Collection: '.$item;
                    break;
                case 'query':
                    $string[] = 'Query: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'fields':
                    $string[] = 'Fields: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'property':
                    $string[] = 'Property: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'object':
                    $string[] = 'Object: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'document':
                    $string[] = 'Document: '.Zend_Debug::dump($item, null, false);
                    break;
                case 'options':
                    $string[] = 'Options: '.Zend_Debug::dump($item, null, false);
                    break;
                default:
                    $string[] = $key.': '.$item;
            }
        }

        return $this->queryStart(implode(' | ', $string), $queryType);
    }
}
