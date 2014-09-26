<?php

namespace Norm\Test\Driver;

use Norm\Model;
use Norm\Collection;

abstract class AbstractConnectionTest extends \PHPUnit_Framework_TestCase
{
    protected $connection;

    public function getConnection()
    {
        return $this->connection;
    }

    public function testUnmarshall()
    {
        $uniqid = uniqid();
        $origin = array(
            'id' => $uniqid,
            'field_a' => 'Field A',
            'field_b' => 'Field B',
        );
        $result = $this->connection->unmarshall($origin);

        $message = 'Method Connection::prepare() expected the field [id] value is copied to field [$id].';
        $this->assertEquals($origin['id'], $result['$id'], $message);
    }

    // public function getCollection()
    // {
    //     $collection = new Collection(array(
    //         'name' => 'test',
    //         'connection' => $this->getConnection(),
    //     ));
    //     return $collection;
    // }
}
