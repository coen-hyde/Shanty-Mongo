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
        $options = $this->getOptions();
        Shanty_Mongo::addConnections($options);
        return $this;
    }
}