<?php

namespace Acme\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

interface UserWrapperInterface
{
    function setUser(UserInterface $user);

    function getUser();
} 