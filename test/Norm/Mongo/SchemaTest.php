<?php

namespace Norm\Mongo;

use Norm\Norm;
use Norm\Collection;

require_once('Fixture.php');

class SchemaTest extends \PHPUnit_Framework_TestCase {
    private $connection;

    public function setUp() {
        Norm::init(Fixture::config('norm.databases'), Fixture::config('norm.schemas'));

        $this->connection = Norm::getConnection();

        $db = $this->connection->getDB();
        $db->drop();
        $db->createCollection("user", false);

        $db->user->insert(array(
            "firstName" => "adoel",
            "lastName" => "razman",
        ));
    }

    public function testSchema() {
        $collection = Norm::factory('User');

        $this->assertTrue($collection->schema()->get('username') instanceof \Norm\Schema\String);

    }
}