<?php

namespace Norm\Mysql;

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MysqlConnection;

require_once('Fixture.php');

class NormTest extends \PHPUnit_Framework_TestCase {

    private $connection;

    public function setUp() {
        Norm::init(Fixture::config('norm.databases'));

        $this->db = Norm::getDB();
    }

    public function testGetConnection() {
        $connection = Norm::getConnection();
        $this->assertTrue($connection instanceof Connection,
            'connection is instance of Connection class');

        $connection = Norm::getConnection('mysql');
        $this->assertTrue($connection instanceof MysqlConnection,
            'our connection (based) on configuration is instance of MysqlConnection');
    }

    public function testAsConnectionProxy() {
        $options = Norm::getConnection()->getOptions();
        $this->assertEquals($options['name'], 'mysql', 'our connection is mysql as it is in our configuration');
    }

}
