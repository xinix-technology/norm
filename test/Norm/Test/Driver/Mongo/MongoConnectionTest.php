<?php

namespace Norm\Test\Mongo;

use Norm\Test\Driver\AbstractConnectionTest;
use Norm\Type\DateTime;
use Norm\Collection;

class MongoConnectionTest extends AbstractConnectionTest
{
    protected $clazz = 'Norm\\Connection\\MongoConnection';

    protected $cursorClazz = 'Norm\\Cursor\\MongoCursor';

    public function getConnection()
    {
        $Clazz = $this->clazz;

        $options = array(
            'name' => 'default',
            'database' => 'test_norm',
        );

        $this->connection = new $Clazz($options);

        $db = $this->connection->getRaw();
        $db->drop();
        $db->createCollection('test_collection', false);

        $db->test_collection->insert(array(
            "first_name" => "putra",
            "last_name" => "pramana",
        ));

        $db->test_collection->insert(array(
            "first_name" => "farid",
            "last_name" => "hidayat",
        ));

        $db->test_collection->insert(array(
            "first_name" => "pendi",
            "last_name" => "setiawan",
        ));

        return $this->connection;
    }

    public function testConstruct()
    {
        parent::testConstruct();

        $Driver = $this->clazz;
        try {
            $connection = new $Driver();
            $this->assertTrue(false, 'Connection::initialize() expected throw exception without database option');
        } catch (\Exception $e) {
            // noop
        }
    }

    public function testGetRaw()
    {
        $raw = $this->connection->getRaw();
        $this->assertInstanceOf('MongoDB', $raw);

        parent::testGetRaw();
    }

    public function testUnmarshall()
    {
        $uniqid = uniqid();
        $origin = array(
            '_id' => new \MongoId(),
            'field_string' => 'Field String',
            'field_date' => new \MongoDate(),
        );

        $result = $this->connection->unmarshall($origin);

        $message = 'Connection::unmarshall() expected the field [_id] ' +
            'value is copied to field [$id] string-typed.';
        $this->assertEquals($origin['_id'], $result['$id'], $message);
        $this->assertTrue(is_string($result['$id']), $message);

        $message = 'Connection::unmarshall() expected the field [_id] ' +
            'value is copied to field [$id] string-typed.';
        $this->assertEquals($origin['_id'], $result['$id'], $message);

        $message = 'Connection::unmarshall() expected the instanceof ' +
            '\MongoDate converted to \Norm\Type\DateTime.';

        $this->assertInstanceOf('\\DateTime', $result['field_date'], $message);
    }

    public function testMarshall()
    {
        parent::testMarshall();

        $uniqid = uniqid();
        $origin = array(
            '$id' => $uniqid,
            'field_string' => 'Field String',
            'field_date' => new DateTime(),
        );
        $result = $this->connection->marshall($origin);

        $message = 'Connection::marshall() expected the field [$id] removed.';
        $this->assertTrue(!isset($result['$id']), $message);
        $this->assertTrue(!isset($result['id']), $message);

        $message = 'Connection::marshall() expected the string-typed leave intact.';
        $this->assertEquals('Field String', $result['field_string'], $message);
        $this->assertTrue(is_string($result['field_string']), $message);

        $message = 'Connection::marshall() expected the DateTime converted to MongoDate.';
        $this->assertInstanceOf('MongoDate', $result['field_date'], $message);
    }

    public function testPersist()
    {
        $document = array(
            'first_name' => 'persisted-'.uniqid(),
            'last_name' => 'object',
        );

        $result = $this->connection->persist('test_collection', $document);

        $message = 'Connection::persist() expected return document if succeed';
        $this->assertTrue(is_array($result), $message);
        $this->assertEquals($document['first_name'], @$result['first_name'], $message);
        $message = 'Connection::persist() expected returned document have id';
        $this->assertNotEmpty(@$result['$id'], $message);
    }

    public function testRemove()
    {
        $db = $this->connection->getRaw();

        $result = $this->connection->remove('test_collection', array(
            'first_name' => 'putra',
        ));

        $this->assertNull($result, 'Connection::remove() expected return null');
    }
}
