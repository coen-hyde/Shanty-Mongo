<?php

require_once 'Shanty/Mongo/Document.php';

/**
 * @category   Shanty
 * @package    Shanty_Mongo
 * @copyright  Shanty Tech Pty Ltd
 * @license    New BSD License
 * @author     Chris Jones <leeked@gmail.com>
 */
class Shanty_Auth_Adapter_Mongodb implements Zend_Auth_Adapter_Interface
{   
    /**
     * @var Shanty_Mongo_Document 
     */
    protected $_documentClass;
    
    /**
     * Field to use as the identity
     *
     * @var string
     */
    protected $_identityField = null;
    
    /**
     * Field to be used as the credentials
     *
     * @var string
     */
    protected $_credentialField = null;

    /**
     * Identity value
     *
     * @var string
     */
    protected $_identity = null;

    /**
     * Credential value
     *
     * @var string
     */
    protected $_credential = null;

    /**
     * @var array
     */
    protected $_authenticateResultInfo = null;

    /**
     * Results of database authentication query
     *
     * @var array
     */
    protected $_resultDocument = null;
       
    /**
     * __construct() - Sets configuration options
     *
     * @return void
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } else if ($options instanceof Zend_Config) {
            $this->setOptions($options->toArray());
        }
    }
    
    /**
     * Set options from array
     *
     * @param array $options Configuration for auth adapter
     * @return Shanty_Auth_Adapter_Mongodb
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('documentClass', $options)) {           
            if ($options['documentClass'] instanceOf Shanty_Mongo_Document) {
                $this->_documentClass = $options['documentClass'];
            } elseif (is_string($options['documentClass'])) {
                $this->_documentClass = new $options['documentClass']();
            }
        } else {
            $this->_documentClass = new Shanty_Mongo_Document();
        }
               
        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'identityfield':
                    $this->setIdentityField($value);
                    break;
                case 'credentialfield':
                    $this->setCredentialField($value);
                    break;
            }
        }

        return $this;
    }
    
    /**
     * Set the field name to be used as the identity field
     *
     * @param string $identityField
     * @return Shanty_Auth_Adapter_Mongodb
     */
    public function setIdentityField($identityField)
    {
        $this->_identityField = $identityField;
        return $this;
    }

    /**
     * Set the field name to be used as the credential field
     *
     * @param string $credentialField
     * @return Shanty_Auth_Adapter_Mongodb
     */
    public function setCredentialField($credentialField)
    {
        $this->_credentialField = $credentialField;
        return $this;
    }
    
    /**
     * Set the value to be used as the identity
     *
     * @param string $value
     * @return Shanty_Auth_Adapter_Mongodb
     */
    public function setIdentity($value)
    {
        $this->_identity = $value;
        return $this;
    }

    /**
     * Set the credential value to be used
     *
     * @param string $credential
     * @return Shanty_Auth_Adapter_Mongodb
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }
    
    /**
     * Defined by Zend_Auth_Adapter_Interface. This method is called to attempt an authentication. 
     * Previous to this call, this adapter would have already been configured with all necessary 
     * information to successfully connect to a database collection and attempt to find a record 
     * matching the provided identity.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_authenticateSetup();
        
        $documentClass    = $this->_documentClass;
        $resultIdentities = $documentClass::all(array(
            $this->_identityField   => $this->_identity,
            $this->_credentialField => $this->_credential
        ));
               
        if (($authResult = $this->_authenticateValidateResultSet($resultIdentities)) instanceof Zend_Auth_Result) {
            return $authResult;
        }
        
        foreach ($resultIdentities as $document) {
            $authResult = $this->_authenticateValidateResult($document);
            return $authResult;
        }
    }
    
    /**
     * This method abstracts the steps involved with making sure that this adapter was indeed setup 
     * properly with all required pieces of information.
     *
     * @throws Zend_Auth_Adapter_Exception - in the event that setup was not done properly
     * @return true
     */
    protected function _authenticateSetup()
    {
        $exception = null;
        
        if (!($this->_documentClass instanceOf Shanty_Mongo_Document)) {
            $exception = 'A document class must be supplied for the Shanty_Auth_Adapter_Mongodb authentication adapter.';
        } elseif ($this->_identityField == '') {
            $exception = 'An identity field must be supplied for the Shanty_Auth_Adapter_Mongodb authentication adapter.';
        } elseif ($this->_credentialField == '') {
            $exception = 'A credential field must be supplied for the Shanty_Auth_Adapter_Mongodb authentication adapter.';
        } elseif ($this->_identity == '') {
            $exception = 'A value for the identity was not provided prior to authentication with Shanty_Auth_Adapter_Mongodb.';
        } elseif ($this->_credential === null) {
            $exception = 'A credential value was not provided prior to authentication with Shanty_Auth_Adapter_Mongodb.';
        }

        if (null !== $exception) {
            /**
           * @see Zend_Auth_Adapter_Exception
           */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception($exception);
        }

        $this->_authenticateResultInfo = array(
            'code'     => Zend_Auth_Result::FAILURE,
            'identity' => $this->_identity,
            'messages' => array()
        );

        return true;
    }
    
    /**
     * This method attempts to make certain that only one record
     * was returned in the resultset.
     *
     * @param array $resultIdentities
     * @return true|Zend_Auth_Result
     */
    protected function _authenticateValidateResultSet($resultIdentities)
    {
        if ($resultIdentities->count() == 0 || $resultIdentities == null) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $this->_authenticateResultInfo['messages'][] = 'A record with the supplied identity could not be found.';
            return $this->_authenticateCreateAuthResult();
        } elseif ($resultIdentities->count() > 1) {
            $this->_authenticateResultInfo['code'] = Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS;
            $this->_authenticateResultInfo['messages'][] = 'More than one record matches the supplied identity.';
            return $this->_authenticateCreateAuthResult();
        }
        
        return true;
    }
    
    
    /**
     * This method attempts to validate that the record in the resultset
     * is indeed a record that matched the identity provided to this adapter.
     *
     * @param array $resultIdentity
     * @return Zend_Auth_Result
     */
    protected function _authenticateValidateResult($resultIdentity)
    {
        $this->_resultDocument = $resultIdentity;
        $this->_authenticateResultInfo['code'] = Zend_Auth_Result::SUCCESS;
        $this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
        return $this->_authenticateCreateAuthResult();
    }
    
    /**
     * Creates a Zend_Auth_Result object from the information that has
     * been collected during the authenticate() attempt.
     *
     * @return Zend_Auth_Result
     */
    protected function _authenticateCreateAuthResult()
    {
        return new Zend_Auth_Result(
            $this->_authenticateResultInfo['code'],
            $this->_authenticateResultInfo['identity'],
            $this->_authenticateResultInfo['messages']
        );
    }
}