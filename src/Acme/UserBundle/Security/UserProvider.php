<?php

namespace Acme\UserBundle\Security;

use Acme\UserBundle\Entity\UserWrapperInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\ORM\EntityManager;
use Acme\UserBundle\Entity\User;

class UserProvider implements UserProviderInterface
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {
        $user = $this->em->getRepository('AcmeUserBundle:User')->findOneBy(array('username' => $username));

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        $securityUser = null;
        $reloadedUser = null;

        if ($user instanceof User) {
            $securityUser = $user;
        } elseif ($user instanceof UserWrapperInterface) {
            $securityUser = $user->getUser();
        }

        if ($securityUser && $this->supportsClass(get_class($user))) {
            $reloadedUser = $this->em->getRepository('AcmeUserBundle:User')->findOneBy(array('id' => $securityUser->getId()));
        }

        if (is_null($reloadedUser)) {
            throw new UsernameNotFoundException('not found');
        }

        if ($user instanceof UserWrapperInterface) {
            $user->setUser($reloadedUser);
            $reloadedUser = $user;
        }

        return $reloadedUser;
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return true;
    }
}