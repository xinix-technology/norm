<?php

namespace Norm\Mysql;

use Norm\Norm;

require_once('Fixture.php');

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;

    public function setUp() {
        Norm::init(Fixture::config('norm.databases'));
        Fixture::init();
    }

    public function testConnection() {
        $this->db = Norm::getDB();
        $this->assertNotEmpty($this->db, 'can connect to database with the right configuration');
    }
}
