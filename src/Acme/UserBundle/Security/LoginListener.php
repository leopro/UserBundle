<?php

namespace Acme\UserBundle\Security;

use Acme\UserBundle\Entity\UserWrapperInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginListener
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    private $actors = array('AcmeDemoBundle:Player', 'AcmeDemoBundle:Coach');

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (is_object($user) && $user instanceof UserInterface) {

            $actor = $this->findActor($user);
            if ($actor && $actor instanceof UserWrapperInterface) {
                $actor->setUser($user);
                $event->getAuthenticationToken()->setUser($actor);
            }
        }
    }

    protected function findActor(UserInterface $user, $count = 0)
    {
        $class = $this->actors[$count];
        $actor = $this->em->getRepository($class)->findOneBy(array('user' => $user));
        if (!$actor) {
            return $this->findActor($user, $count+1);
        }

        return $actor;
    }
}