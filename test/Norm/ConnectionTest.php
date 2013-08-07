<?php

use Norm\Norm;
use Norm\Collection;

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;

    public function setUp() {
        $config = array(
            'mongo' => array(
                'driver' => '\\Norm\\Connection\\MongoConnection',
                'database' => 'test',
            ),
        );
        Norm::init($config);
        $this->connection = Norm::getConnection();

        $db = $this->connection->getDB();
        $db->drop();
        $db->createCollection("user",false);

        $db->people->insert(array(
            "firstName" => "adoel",
            "lastName" => "razman",
        ));

        $db->people->insert(array(
            "firstName" => "putra",
            "lastName" => "pramana",
        ));

        $db->people->insert(array(
            "firstName" => "farid",
            "lastName" => "lab",
        ));

        $db->people->insert(array(
            "firstName" => "habib",
            "lastName" => "chalid",
        ));
    }

    public function testListCollections() {
        $collections = $this->connection->listCollections();
        $this->assertEquals($collections[0], 'user');
    }

    // FIXME do we need this?
    // public function testGetCollection() {
    //     $collection = $this->connection->getCollection('User');
    //     $this->assertTrue($collection instanceof Collection, "Is Connection::getCollection() return Collection object");
    // }
}