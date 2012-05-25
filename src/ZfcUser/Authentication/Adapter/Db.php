<?php

namespace ZfcUser\Authentication\Adapter;

use ZfcUser\Authentication\Adapter\AdapterChainEvent as AuthEvent,
    Zend\Authentication\Result as AuthenticationResult,
    Zend\ServiceManager\ServiceManagerAwareInterface,
    Zend\ServiceManager\ServiceManager,
    ZfcUser\Module as ZfcUser,
    ZfcUser\Model\UserMapperInterface,
    ZfcUser\Util\Password,
    DateTime;

class Db extends AbstractAdapter implements ServiceManagerAwareInterface
{
    /**
     * @var UserMapperInterface
     */
    protected $userMapper;

    /**
     * @var closure / invokable object
     */
    protected $credentialPreprocessor;

    /**
     * @var ServiceManager
     */
    protected $locator;

    public function authenticate(AuthEvent $e)
    {
        if ($this->isSatisfied()) {
            $storage = $this->getStorage()->read();
            $e->setIdentity($storage['identity'])
              ->setCode(AuthenticationResult::SUCCESS)
              ->setMessages(array('Authentication successful.'));
            return;
        }

        $identity   = $e->getRequest()->post()->get('identity');
        $credential = $e->getRequest()->post()->get('credential');
        $credential = $this->preProcessCredential($credential);
        
        $userObject = $this->getUserMapper()->findByEmail($identity);

        if (!$userObject && ZfcUser::getOption('enable_username')) {
            // Auth by username
            $userObject = $this->getUserMapper()->findByUsername($identity);
        }
        if (!$userObject) {
            $e->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
              ->setMessages(array('A record with the supplied identity could not be found.'));
            $this->setSatisfied(false);
            return false;
        }

        $credentialHash = Password::hash($credential, $userObject->getPassword());

        if ($credentialHash !== $userObject->getPassword()) {
            // Password does not match
            $e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
              ->setMessages(array('Supplied credential is invalid.'));
            $this->setSatisfied(false);
            return false;
        }

        // Success!
        $e->setIdentity($userObject->getUserId());
        $this->updateUserLastLogin($userObject)
             ->updateUserPasswordHash($userObject, $credential)
             ->setSatisfied(true);
        $storage = $this->getStorage()->read();
        $storage['identity'] = $e->getIdentity();
        $this->getStorage()->write($storage);
        $e->setCode(AuthenticationResult::SUCCESS)
          ->setMessages(array('Authentication successful.'));
    }

    protected function updateUserPasswordHash($userObject, $password)
    {
        $newHash = Password::hash($password);
        if ($newHash === $userObject->getPassword()) return $this;

        $userObject->setPassword($newHash);

        $this->getUserMapper()->persist($userObject);
        return $this;
    }

    protected function updateUserLastLogin($userObject)
    {
        $userObject->setLastLogin(new DateTime('now'))
                   ->setLastIp($_SERVER['REMOTE_ADDR']);

        $this->getUserMapper()->persist($userObject);
        return $this;
    }

    public function preprocessCredential($credential)
    {
        $processor = $this->getCredentialPreprocessor();
        if (is_callable($processor)) {
            return $processor($credential);
        }
        return $credential;
    }
 
    /**
     * Get credentialPreprocessor.
     *
     * @return credentialPreprocessor
     */
    public function getCredentialPreprocessor()
    {
        return $this->credentialPreprocessor;
    }
 
    /**
     * Set credentialPreprocessor.
     *
     * @param $credentialPreprocessor the value to be set
     */
    public function setCredentialPreprocessor($credentialPreprocessor)
    {
        $this->credentialPreprocessor = $credentialPreprocessor;
        return $this;
    }

    /**
     * getUserMapper 
     * 
     * @return UserMapperInterface
     */
    public function getUserMapper()
    {
        if (null === $this->userMapper) {
            $this->userMapper = $this->getServiceManager()->get('zfcuser_user_mapper');
        }
        return $this->userMapper;
    }

    /**
     * setUserMapper
     *
     * @param UserMapperInterface $userMapper
     * @return User
     */
    public function setUserMapper(UserMapperInterface $userMapper)
    {
        $this->userMapper = $userMapper;
        return $this;
    }

    /**
     * Retrieve locator instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->locator;
    }

    /**
     * Set locator instance
     *
     * @param  ServiceManager $locator
     * @return void
     */
    public function setServiceManager(ServiceManager $locator)
    {
        $this->locator = $locator;
    }
}
