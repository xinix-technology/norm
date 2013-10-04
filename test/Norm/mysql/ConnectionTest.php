<?php

use Norm\Norm;

class ConnectionTest extends \PHPUnit_Framework_TestCase {
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
    }

    public function testConnection() {
        $this->db = Norm::getDB();
        $this->assertNotEmpty($this->db);
    }
}
