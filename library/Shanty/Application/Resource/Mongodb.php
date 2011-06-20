<?php

class Shanty_Application_Resource_Mongodb extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Initialize the Database Connections
     *
     * @return Shanty_Application_Resource_Mongodb
     */
    public function init()
    {
        Shanty_Mongo::addConnections($this->getOptions());
        return $this;
    }
}