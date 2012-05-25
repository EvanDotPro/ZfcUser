<?php

namespace ZfcUser\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\ServiceManager\ServiceManagerAwareInterface,
    Zend\ServiceManager\ServiceManager,
    Zend\Authentication\AuthenticationService,
    ZfcUser\Authentication\Adapter\AdapterChain as AuthAdapter;

class ZfcUserAuthentication extends AbstractPlugin implements ServiceManagerAwareInterface
{
    /**
     * @var AuthAdapter
     */
    protected $authAdapter;

    /**
     * @var AuthenticationSerivce
     */
    protected $authService;

    /**
     * @var ServiceManager
     */
    protected $locator;

    /**
     * Proxy convenience method 
     * 
     * @return bool
     */
    public function hasIdentity()
    {
        return $this->getAuthService()->hasIdentity();
    }
    
    /**
     * Proxy convenience method 
     * 
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->getAuthService()->getIdentity();
    }

    /**
     * Get authAdapter.
     *
     * @return ZfcUserAuthentication
     */
    public function getAuthAdapter()
    {
        if (null === $this->authAdapter) {
            $this->authAdapter = $this->getServiceManager()->get('ZfcUser\Authentication\Adapter\AdapterChain');
        }
        return $this->authAdapter;
    }
 
    /**
     * Set authAdapter.
     *
     * @param authAdapter $authAdapter
     */
    public function setAuthAdapter(AuthAdapter $authAdapter)
    {
        $this->authAdapter = $authAdapter;
        return $this;
    }
 
    /**
     * Get authService.
     *
     * @return AuthenticationService
     */
    public function getAuthService()
    {
        if (null === $this->authService) {
            $this->authService = $this->getServiceManager()->get('zfcuser_auth_service');
        }
        return $this->authService;
    }
 
    /**
     * Set authService.
     *
     * @param AuthenticationService $authService
     */
    public function setAuthService(AuthenticationService $authService)
    {
        $this->authService = $authService;
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
