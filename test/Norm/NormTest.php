<?php

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MongoConnection;

class NormTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $config = array(
            'mongo' => array(
                'driver' => '\\Norm\\Connection\\MongoConnection',
                'database' => 'think',
            ),
        );
        Norm::init($config);
    }

    public function testGetConnection() {
        $connection = Norm::getConnection();
        $this->assertTrue($connection instanceof Connection);

        $connection = Norm::getConnection('mongo');
        $this->assertTrue($connection instanceof MongoConnection);
    }

    public function testAsConnectionProxy() {
        $options = Norm::getConnection()->getOptions();
        $this->assertEquals($options['name'], 'mongo');
    }

}