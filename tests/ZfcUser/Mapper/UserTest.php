<?php
namespace ZfcUserTest\Mapper;

use PHPUnit_Framework_TestCase;
use ZfcUserTest\Bootstrap;
use ZfcUser\Mapper\User as UserMapper;
use ZfcUser\Entity\User;

class UserTest extends PHPUnit_Framework_TestCase
{
    protected $mapper;

    public function setup()
    {
        copy('data/users-test.db.dist', 'data/users-test.db');
        $this->mapper = Bootstrap::getServiceManager()->get('zfcuser_user_mapper');
    }

    public function tearDown()
    {
        unlink('data/users-test.db');
    }

    protected function insertDummyUser()
    {
        $user = new User;
        $user->setEmail('jane@doe.tld');
        $user->setDisplayName('Jane Doe');
        $user->setPassword('passwordhash');
        $this->mapper->insert($user);
        return $user;
    }

    public function testCanInsertUser()
    {
        $result = $this->mapper->findById(1);
        $this->assertFalse($result);

        $user = $this->insertDummyUser();
        $this->assertEquals(1, $user->getId());

        $result = $this->mapper->findById(1);
        $this->assertInstanceOf('ZfcUser\Entity\User', $result);
    }
}

