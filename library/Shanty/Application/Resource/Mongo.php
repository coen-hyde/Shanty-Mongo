<?php

/**
 * MongoDB application resource
 *
 * Example configuration:
 * <pre>
 * ; Tell our bootstrap where to look for custom resource plugins.
 * ; We have a custom resource plugin(initializer) in Shanty/Application/Resource
 * ; with class prefix of Shanty_Application_Resource. 
 * pluginPaths.Shanty_Application_Resource = "Shanty/Application/Resource"
 * 
 * ; Scenario 1. Single host config
 * resources.mongo.host = "<hostname or IP>"
 * resources.mongo.port = 27017
 * resources.mongo.username = ""
 * resources.mongo.password = ""
 * resources.mongo.database = ""
 * 
 * ; Scenario 2.  Single master single slave config
 * resources.mongo.master.host = 'mongomaster.local'
 * resources.mongo.master.port = 27017
 * resources.mongo.master.username = ""
 * resources.mongo.master.password = ""
 * resources.mongo.master.database = ""
 * resources.mongo.slave.host = 'mongoslave.local'
 * resources.mongo.slave.port = 27017
 * resources.mongo.slave.username = ""
 * resources.mongo.slave.password = ""
 * resources.mongo.slave.database = ""
 * </pre>
 *
 * Resource for settings MongoDB connection options
 *
 * @author JackalHu kgo_yoi@hotmail.com
 * @see https://github.com/coen-hyde/Shanty-Mongo/wiki/Connections
 * @see http://framework.zend.com/manual/1.12/en/zend.application.theory-of-operation.html#zend.application.theory-of-operation.bootstrap.resource-plugins
 * @see http://www.zendcasts.com/creating-custom-application-resources/2011/06/
 */

class Shanty_Application_Resource_Mongo extends Zend_Application_Resource_ResourceAbstract {

    public function init() {
        Shanty_Mongo::addConnections($this->getOptions());
    }

}
