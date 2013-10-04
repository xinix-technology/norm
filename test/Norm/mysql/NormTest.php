<?php

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MysqlConnection;

class NormTest extends \PHPUnit_Framework_TestCase {

    private $connection;

    public function setUp() {
        $config = array(
            'mysql' => array(
                'driver' => '\\Norm\\Connection\\MysqlConnection',
                'database' => 'test',
                'username' => 'root',
                'password' => 'password',
            ),
        );

        Norm::init($config);
        $this->db = Norm::getDB();
    }

    public function testGetConnection() {
        $connection = Norm::getConnection();
        $this->assertTrue($connection instanceof Connection);

        $connection = Norm::getConnection('mysql');
        $this->assertTrue($connection instanceof MysqlConnection);
    }

    public function testAsConnectionProxy() {
        $options = Norm::getConnection()->getOptions();
        $this->assertEquals($options['name'], 'mysql');
    }

}
