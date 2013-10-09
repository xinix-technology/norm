<?php

namespace Norm\Mongo;

use Norm\Norm;
use Norm\Collection;

require_once('Fixture.php');

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;

    public function setUp() {
        Norm::init(Fixture::config('norm.databases'));

        $this->connection = Norm::getConnection();

        $db = $this->connection->getDB();
        $db->drop();
        $db->createCollection("user", false);

        $db->user->insert(array(
            "firstName" => "adoel",
            "lastName" => "razman",
        ));

        // $db->user->insert(array(
        //     "firstName" => "putra",
        //     "lastName" => "pramana",
        // ));

        // $db->user->insert(array(
        //     "firstName" => "farid",
        //     "lastName" => "lab",
        // ));

        // $db->user->insert(array(
        //     "firstName" => "habib",
        //     "lastName" => "chalid",
        // ));
    }

    public function testListCollections() {
        $collections = $this->connection->listCollections();
        $this->assertEquals($collections[0], 'user');
    }
}