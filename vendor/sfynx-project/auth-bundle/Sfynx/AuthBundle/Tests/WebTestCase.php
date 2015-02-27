<?php
/**
 * This file is part of the <Auth> project.
 *
 * @subpackage Auth
 * @package    Tests
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 * @since      2015-01-08
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Sfynx\AuthBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Client;
use Doctrine\ORM\Tools\SchemaTool;
use Sfynx\AuthBundle\DataFixtures\ORM\UsersFixtures;

/**
 * This is the base test case for all functional tests.
 * It bootstraps the database before each test class.
 *
 * @subpackage Auth
 * @package    Tests
 * @author     Etienne de Longeaux <etienne.delongeaux@gmail.com>
 */
abstract class WebTestCase extends BaseWebTestCase
{
    const USER_EMAIL     = 'user@example.org';
    const USER_USERNAME  = 'user123';
    const USER_PASS      = 'testtest';
    const USER_PASSWORD  = 'jMhPNtk/r/aDmrihsK2jw+D+zpnSxBxCL5v1tvCWZd/I4N7/gJiAjVPS0Xy2XkbVpVOPjgSHBBsskDmHWqEo4Q==';
    
    const ADMIN_EMAIL    = 'admin@example.org';
    const ADMIN_USERNAME = 'admin123';
    const ADMIN_PASS     = 'testtest';
    const ADMIN_PASSWORD = 'jMhPNtk/r/aDmrihsK2jw+D+zpnSxBxCL5v1tvCWZd/I4N7/gJiAjVPS0Xy2XkbVpVOPjgSHBBsskDmHWqEo4Q==';

    const URL_CONNECTION   = '/login';
    const URL_DECONNECTION = '/logout';

    /** @var Application */
    protected static $application;
    
    protected static $kernel;
    
    protected static $em;
    
    protected static $metadata;
    
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        
        static::$em = static::$kernel->getContainer()->get('doctrine')->getManager();
        
        $schemaTool = new SchemaTool(static::$em);
        static::$metadata = static::$em->getMetadataFactory()->getAllMetadata();
    }    
    
    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);

        return self::getApplication()->run(new \Symfony\Component\Console\Input\StringInput($command));
    }

    protected static function getApplication()
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new \Symfony\Bundle\FrameworkBundle\Console\Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }


    protected static function loadFixtures()
    {
        self::runCommand('doctrine:fixtures:load --append --fixtures=vendor/sfynx-project/auth-bundle/Sfynx/AuthBundle --env=test');
    }
    
    protected static function emptyDatabase()
    {
        self::runCommand('doctrine:database:drop --force --env=test');
        self::runCommand('doctrine:database:create --env=test');
        self::runCommand('doctrine:schema:create --env=test');
    }
    
    protected static function emptyCache()
    {
        self::runCommand('rm -rf app/cache/test/*');
    } 
    
    protected static function updateSchema()
    {
        self::runCommand('doctrine:schema:update --force --env=test');
    }     

    /**
     * @param  \Symfony\Bundle\FrameworkBundle\Client $client
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function loginRoleUser(Client $client = null)
    {
        if (!$client) {
            $client = static::createClient();
        }
        $client->request('GET', self::URL_CONNECTION);
        $form = $client->getCrawler()->filter('form input[type=submit]')->first()->form();
        $client->submit(
            $form,
            array(
                'roles' => '{"0":"ROLE_USER"}',
                '_username' => self::USER_USERNAME,
                '_password' => self::USER_PASS
            )
        );
        $client->followRedirect();

        return $client;
    }

    /**
     * @param  \Symfony\Bundle\FrameworkBundle\Client $client
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function loginRoleAdmin(Client $client = null)
    {
        if (!$client) {
            $client = static::createClient();
        }
        $client->request('GET', self::URL_CONNECTION);        
        
        $form = $client->getCrawler()->filter('form input[type=submit]')->first()->form();
        $client->submit(
            $form,
            array(
                'roles' => '{"0":"ROLE_ADMIN","1":"ROLE_SUPER_ADMIN"}',
                '_username' => self::ADMIN_USERNAME,
                '_password' => self::ADMIN_PASS
            )
        );
        $client->followRedirect();

        return $client;
    }

    /**
     * @param  \Symfony\Bundle\FrameworkBundle\Client $client
     * @return WebTestCase
     */
    protected function logout(Client $client)
    {
        $client->request('GET', static::URL_DECONNECTION);
        $client->followRedirect();

        return $this;
    }

    /**
     * Asserts that client's response is of given status code.
     * If not it traverses the response to find symfony error and display it.
     *
     * @param integer $statusCode
     * @param Client  $client
     */
    protected function assertStatusCode($statusCode, Client $client)
    {
        /** @var $response Response */
        $response = $client->getResponse();
        if ($response->isServerError() && $response->getStatusCode() >= 500 && $response->getStatusCode() < 600) {
            $this->assertEquals(
                $statusCode,
                $response->getStatusCode(),
                $client->getCrawler()->filter('.block_exception h1')->text()
            );
        } else {
            $this->assertEquals($statusCode, $response->getStatusCode());
        }
    }

    protected function assertSEOCompatible(Client $client, $type = 'article')
    {
        $crawler = $client->getCrawler();
        $url = $client->getRequest()->getUri();
        $title = $crawler->filter('title')->text();

        // title
        $this->assertNotEmpty($title, 'The title is empty.');
        // meta
        $this->assertCount(1, $crawler->filter('meta[charset=UTF-8]'));
        $this->assertCount(1, $crawler->filter('meta[property="og:title"][content="' . $title . '"]'));
        $this->assertCount(1, $crawler->filter('meta[property="og:type"][content="' . $type . '"]'));
        $this->assertCount(1, $crawler->filter('meta[property="og:url"][content="' . $url . '"]'));
        // img
        // $this->assertCount(0, $crawler->filter('img[alt=""]'));
        $crawler->filter('img:not([alt])')->each(function ($node, $i) {
            $this->assertTrue( false, 'An image with no alt attribute has been found src='. $node->attr('src'));
        });
    }

    protected function dumpCrawler($crawler)
    {
        foreach ($crawler as $dom) {
            print $dom->ownerDocument->saveHTML($dom) . PHP_EOL;
        }
    }

    protected function assertHasPropelHandlerCalled($profile, $event)
    {
        $calledEvents = $profile->getCollector('propel_events')->getCalledListeners();
        $this->assertContains($event, implode(array_keys($calledEvents)));
    }
}
