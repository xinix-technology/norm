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
            'field_string' => 'Field String',
        );
        $result = $this->connection->unmarshall($origin);

        $message = 'Method Connection::unmarshall() expected the field [id] value is copied to field [$id].';
        $this->assertEquals($origin['id'], $result['$id'], $message);

        $message = 'Method Connection::unmarshall() expected the string-typed leave intact.';
        $this->assertEquals('Field String', $result['field_string'], $message);
        $this->assertTrue(is_string($result['field_string']), $message);
    }

    public function testMarshall()
    {
        $uniqid = uniqid();
        $origin = array(
            '$id' => $uniqid,
            'field_string' => 'Field String',
        );
        $result = $this->connection->marshall($origin);

        $message = 'Method Connection::marshall() expected the field [$id] removed.';
        $this->assertTrue(!isset($result['$id']), $message);
        $this->assertTrue(!isset($result['id']), $message);

        $message = 'Method Connection::marshall() expected the string-typed leave intact.';
        $this->assertEquals('Field String', $result['field_string'], $message);
        $this->assertTrue(is_string($result['field_string']), $message);
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
