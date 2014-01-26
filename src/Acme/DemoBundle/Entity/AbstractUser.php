<?php

namespace Acme\DemoBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Acme\UserBundle\Entity\UserWrapperInterface;

abstract class AbstractUser implements UserInterface, UserWrapperInterface
{
    public function getRoles()
    {
        return $this->user->getRoles();
    }

    public function getPassword()
    {
        return $this->user->getPassword();
    }

    public function getSalt()
    {
        return $this->user->getSalt();
    }

    public function getUsername()
    {
        return $this->user->getUsername();
    }

    public function eraseCredentials()
    {
        $this->user->eraseCredentials();
    }

    function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
} 