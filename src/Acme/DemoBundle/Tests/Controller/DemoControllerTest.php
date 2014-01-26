<?php

namespace Acme\DemoBundle\Tests\Controller;

use Acme\DemoBundle\Entity\Coach;
use Acme\DemoBundle\Entity\Player;
use Acme\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;
use Symfony\Bundle\FrameworkBundle\Client;

class DemoControllerTest extends WebTestCase
{
    public function saveOutput(Client $client, $domain = null, $path = null, $delete = false, $browser = 'firefox')
    {
        if (is_null($path)) {
            $path = "/../../../../../web/test_out.html";
        }

        file_put_contents(__DIR__ . $path, $client->getResponse()->getContent());

        if (!is_null($domain)) {
            $process = new Process($browser . ' "'.$domain.'/test_out.html"');
            $process->start();
            sleep(1);

            if ($delete) {
                unlink(__DIR__ . $path);
            }
        }
    }

    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        
        $factory = $kernel->getContainer()->get('security.encoder_factory');
        $this->em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        
        $user = new User();
        $user->setUsername('player@my.lo');
        $user->setEmail('player@my.lo');
        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword('player', $user->getSalt());
        $user->setPassword($password);
        $user->setIsActive(true);
        $this->em->persist($user);

        $user2 = new User();
        $user2->setUsername('coach@my.lo');
        $user2->setEmail('coach@my.lo');
        $encoder = $factory->getEncoder($user2);
        $password = $encoder->encodePassword('coach', $user2->getSalt());
        $user2->setPassword($password);
        $user2->setIsActive(true);
        $this->em->persist($user2);

        $player = new Player();
        $player->setUser($user);
        $player->setName('pippo');
        $player->setRole('difensore');
        $this->em->persist($player);

        $coach = new Coach();
        $coach->setUser($user2);
        $coach->setName('pluto');
        $this->em->persist($coach);

        $this->em->flush();
    }
    
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/demo/hello/Fabien');

        $this->assertGreaterThan(0, $crawler->filter('html:contains("Hello Fabien")')->count());
    }

    public function testSecureSectionAsPlayer()
    {
        $client = static::createClient();

        // goes to the secure page
        $crawler = $client->request('GET', '/demo/secured/hello/World');

        // redirects to the login page
        $crawler = $client->followRedirect();

        // submits the login form
        $form = $crawler->selectButton('Login')->form(array('_username' => 'player@my.lo', '_password' => 'player'));
        $client->submit($form);

        // redirect to the original page (but now authenticated)
        $crawler = $client->followRedirect();

        // check that the page is the right one
        $this->assertCount(1, $crawler->filter('h1.title:contains("Hello World!")'));

        // click on the secure link
        $link = $crawler->selectLink('Hello resource secured')->link();
        $crawler = $client->click($link);

        // check that the page is the right one
        $this->assertCount(1, $crawler->filter('h1.title:contains("Acme\DemoBundle\Entity\Player")'));
        $this->assertRegExp('/UserWrapperInterface/', $crawler->filter('p')->eq(0)->text());
        $this->assertRegExp('/UserInterface/', $crawler->filter('p')->eq(1)->text());
    }

    public function testSecureSectionAsCoach()
    {
        $client = static::createClient();

        // goes to the secure page
        $crawler = $client->request('GET', '/demo/secured/hello/World');

        // redirects to the login page
        $crawler = $client->followRedirect();

        // submits the login form
        $form = $crawler->selectButton('Login')->form(array('_username' => 'coach@my.lo', '_password' => 'coach'));
        $client->submit($form);

        // redirect to the original page (but now authenticated)
        $crawler = $client->followRedirect();

        // check that the page is the right one
        $this->assertCount(1, $crawler->filter('h1.title:contains("Hello World!")'));

        // click on the secure link
        $link = $crawler->selectLink('Hello resource secured')->link();
        $crawler = $client->click($link);

        // check that the page is the right one
        $this->assertCount(1, $crawler->filter('h1.title:contains("Acme\DemoBundle\Entity\Coach")'));
        $this->assertRegExp('/UserWrapperInterface/', $crawler->filter('p')->eq(0)->text());
        $this->assertRegExp('/UserInterface/', $crawler->filter('p')->eq(1)->text());
    }
    
    public function tearDown()
    {
        $player = $this->em->getRepository('AcmeDemoBundle:Player')->findOneByName('pippo');
        $this->em->remove($player);
        $this->em->flush();

        $coach = $this->em->getRepository('AcmeDemoBundle:Coach')->findOneByName('pluto');
        $this->em->remove($coach);
        $this->em->flush();

        $user = $this->em->getRepository('AcmeUserBundle:User')->findOneByUsername('player@my.lo');
        $this->em->remove($user);

        $user2 = $this->em->getRepository('AcmeUserBundle:User')->findOneByUsername('coach@my.lo');
        $this->em->remove($user2);

        $this->em->flush();
    }
}
