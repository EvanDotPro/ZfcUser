<?php

namespace ZfcUser\Service;

use Zend\Authentication\AuthenticationService,
    Zend\Form\Form,
    Zend\EventManager\ListenerAggregate,
    Zend\ServiceManager\ServiceManagerAwareInterface,
    Zend\ServiceManager\ServiceManager,
    DateTime,
    ZfcUser\Util\Password,
    ZfcUser\Model\UserMapperInterface,
    ZfcUser\Model\UserMetaMapperInterface,
    ZfcUser\Module as ZfcUser,
    ZfcBase\EventManager\EventProvider;

class User extends EventProvider implements ServiceManagerAwareInterface
{
    /**
     * @var UserMapperInterface
     */
    protected $userMapper;

    /**
     * @var UserMetaMapperInterface
     */
    protected $userMetaMapper;

    /**
     * @var authService
     */
    protected $authService;

    /**
     * @var ServiceManager
     */
    protected $locator;

    public function updateMeta($key, $value)
    {
        $user = $this->getAuthService()->getIdentity();
        if (!$userMeta = $this->getUserMetaMapper()->get($user->getUserId(), $key)) {
            $class = ZfcUser::getOption('usermeta_model_class');
            $userMeta = new $class;
            $userMeta->setUser($user);
            $userMeta->setMetaKey($key);
            $userMeta->setMeta($value);
            $this->getUserMetaMapper()->add($userMeta);
        }
        if (!$userMeta->getUser()) {
            $userMeta->setUser($user);
        }
        $userMeta->setMeta($value);
        $this->getUserMetaMapper()->update($userMeta);
    }

    /**
     * createFromForm
     *
     * @param Form $form
     * @return ZfcUser\Model\User
     */
    public function createFromForm(Form $form)
    {
        $class = ZfcUser::getOption('user_model_class');
        $user = new $class;

        $data = $form->getData();

        $user->setEmail($data['email'])
             ->setPassword(Password::hash($data['password']))
             ->setRegisterIp($_SERVER['REMOTE_ADDR'])
             ->setRegisterTime(new DateTime('now'))
             ->setEnabled(true);
        if (ZfcUser::getOption('require_activation')) {
            $user->setActive(false);
        } else {
            $user->setActive(true);
        }
        if (ZfcUser::getOption('enable_username')) {
            $user->setUsername($data['username']);
        }
        if (ZfcUser::getOption('enable_display_name')) {
            $user->setDisplayName($data['display_name']);
        }
        $this->events()->trigger(__FUNCTION__, $this, array('user' => $user, 'form' => $form));
        $this->getUserMapper()->persist($user);
        return $user;
    }

    /**
     * Get a user entity by their username
     *
     * @param string $username
     * @return ZfcUser\Model\User
     */
    public function getByUsername($username)
    {
        return $this->getUserMapper()->findByUsername($username);
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
     * getUserMetaMapper 
     * 
     * @return UserMetaMapperInterface
     */
    public function getUserMetaMapper()
    {
        if (null === $this->userMetaMapper) {
            $this->userMetaMapper = $this->getServiceManager()->get('zfcuser_usermeta_mapper');
        }
        return $this->userMetaMapper;
    }

    /**
     * setUserMetaMapper
     *
     * @param UserMetaMapperInterface $userMetaMapper
     * @return User
     */
    public function setUserMetaMapper(UserMetaMapperInterface $userMetaMapper)
    {
        $this->userMetaMapper = $userMetaMapper;
        return $this;
    }

    /**
     * getAuthService
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
     * setAuthenticationService
     *
     * @param AuthenticationService $authService
     * @return User
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
