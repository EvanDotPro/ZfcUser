<?php

namespace ZfcUser\Authentication\Storage;

use Zend\Authentication\Storage,
    Zend\ServiceManager\ServiceManagerAwareInterface,
    Zend\ServiceManager\ServiceManager,
    ZfcUser\Model\UserMapperInterface;

class Db implements Storage\StorageInterface, ServiceManagerAwareInterface
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var UserMapperInterface
     */
    protected $userMapper;

    /**
     * @var mixed
     */
    protected $resolvedIdentity;

    /**
     * @var ServiceManager
     */
    protected $locator;

    /**
     * Returns true if and only if storage is empty
     *
     * @throws Zend\Authentication\Storage\Exception If it is impossible to determine whether storage is empty
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->getStorage()->isEmpty();
    }

    /**
     * Returns the contents of storage
     *
     * Behavior is undefined when storage is empty.
     *
     * @throws Zend\Authentication\Storage\Exception If reading contents from storage is impossible
     * @return mixed
     */
    public function read()
    {
        if (null !== $this->resolvedIdentity) {
            return $this->resolvedIdentity;
        }

        $identity = $this->getStorage()->read();

        if (is_int($identity) || is_scalar($identity)) {
            $identity = $this->getUserMapper()->findById($identity);
        }

        if ($identity) {
            $this->resolvedIdentity = $identity;
        } else {
            $this->resolvedIdentity = null;
        }
        
        return $this->resolvedIdentity;
    }

    /**
     * Writes $contents to storage
     *
     * @param  mixed $contents
     * @throws Zend\Authentication\Storage\Exception If writing $contents to storage is impossible
     * @return void
     */
    public function write($contents)
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->write($contents);
    }

    /**
     * Clears contents from storage
     *
     * @throws Zend\Authentication\Storage\Exception If clearing contents from storage is impossible
     * @return void
     */
    public function clear()
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->clear();
    }

    /**
     * getStorage 
     * 
     * @return Storage\StorageInterface
     */
    public function getStorage()
    {
        if (null === $this->storage) {
            $this->setStorage(new Storage\Session);
        }
        return $this->storage;
    }

    /**
     * setStorage
     * 
     * @param Storage\StorageInterface $storage 
     * @access public
     * @return void
     */
    public function setStorage(Storage\StorageInterface $storage)
    {
        $this->storage = $storage;
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
