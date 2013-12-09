<?php

namespace AC\WebServicesBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * This is a base integration test case that you can use for your own tests.  It contains a few convenience methods
 * for making API calls.
 **/
abstract class TestCase extends WebTestCase
{

    /**
     * Shortcut to get client
     */
    protected function getClient()
    {
        $client = static::createClient(array(
            'environment' => 'test',
            'debug' => true
        ));

        return $client;
    }

    /**
     * Shortcut to run a CLI command - returns a... ?
     */
    protected function runCommand($string)
    {
        $command = sprintf('%s --quiet --env=test', $string);
        $k = $this->createKernel();
        $app = new Application($k);
        $app->setAutoExit(false);

        return $app->run(new StringInput($string), new NullOutput());
    }

    /**
     * Shortcut to get the Container
     */
    protected function getContainer()
    {
        $k = $this->createKernel();
        $k->boot();

        return $k->getContainer();
    }

    /**
     * Shortcut to make a request and get the returned Response instance.
     */
    public function callApi($method, $uri, $params = array(), $files = array(), $server = array(), $content = null, $changehistory = true)
    {
        $server['SERVER_NAME'] = '127.0.0.1';
        $client = static::createClient(array(
            'environment' => 'test',
            'debug' => true
        ));

        $client->request($method, $uri, $params, $files, $server, $content, $changehistory);

        return $client->getResponse();
    }

}
