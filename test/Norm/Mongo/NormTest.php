<?php

namespace Norm\Mongo;

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MongoConnection;

require_once('Fixture.php');

class NormTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        Norm::init(Fixture::config('norm.databases'));
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