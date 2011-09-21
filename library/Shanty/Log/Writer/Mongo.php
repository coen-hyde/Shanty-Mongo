<?php

/** Zend_Log_Writer_Abstract */
require_once 'Zend/Log/Writer/Abstract.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Girish Nath
 */
class Shanty_Log_Writer_Mongo extends Zend_Log_Writer_Abstract
{
    /**
     * Instance of Mongo document
     *
     * @var object Mongo document that extends Shanty_Mongo_Document
     */
    private $_documentClass;

    /**
     * Relates database columns names to log data field keys.
     *
     * @var null|array
     */
    private $_columnMap;

    /**
     * Class constructor
     *
     * @param string $documentClass         String representing your Mongo document class name§
     * @param array $columnMap
     * @return void
     */
    public function __construct($documentClass, $columnMap = null)
    {
        if (is_string ($documentClass)) {
            $this->_documentClass = new $documentClass;
        }
        else {
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Document class name must be a string');
        }
        $this->_columnMap = $columnMap;
    }

    /**
     * Create a new instance of Shanty_Log_Writer_Mongo
     *
     * @param  array|Zend_Config $config
     * @return Shanty_Log_Writer_Mongo
     */
    static public function factory($config)
    {
        $config = self::_parseConfig($config);
        $config = array_merge(array(
            'documentClass' => null,
            'columnMap' => null,
        ), $config);
        
        if (isset($config['documentclass'])) {
            $config['documentClass'] = $config['documentclass'];
        }

        if (isset($config['columnmap'])) {
            $config['columnMap'] = $config['columnmap'];
        }

        return new self(
            $config['documentClass'],
            $config['columnMap']
        );
    }

    /**
     * Formatting is not possible on this writer
     *
     * @return void
     * @throws Zend_Log_Exception
     */
    public function setFormatter(Zend_Log_Formatter_Interface $formatter)
    {
        require_once 'Zend/Log/Exception.php';
        throw new Zend_Log_Exception(get_class($this) . ' does not support formatting');
    }

    /**
     * Remove reference to the Mongo document 
     *
     * @return void
     */
    public function shutdown()
    {
        $this->_documentClass = null;
    }

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     * @throws Zend_Log_Exception
     */
    protected function _write($event)
    {
        if ($this->_documentClass === null) {
            require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception('Document class is null');
        }

        if ($this->_columnMap === null) {
            $dataToInsert = $event;
        } else {
            $dataToInsert = array();
            foreach ($this->_columnMap as $columnName => $fieldKey) {
                $dataToInsert[$columnName] = $event[$fieldKey];
            }
        }

        // Populate the document and save
        foreach ($dataToInsert as $key => $value) {
            $this->_documentClass->$key = $value;
        }
        $this->_documentClass->save();
        
    }
}