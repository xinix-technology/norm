<?php

namespace Norm\Test\Driver;

use Norm\Model;
use Norm\Collection;

abstract class AbstractConnectionTest extends \PHPUnit_Framework_TestCase
{
    protected $clazz;

    protected $cursorClazz;

    protected $connection;

    abstract public function getConnection();

    public function setUp()
    {
        $this->connection = $this->getConnection();
    }

    public function testConstruct()
    {
        $Driver = $this->clazz;
        try {
            $connection = new $Driver();
            $this->assertTrue(false, 'Connection::initialize() expected throw exception on empty arguments');
        } catch (\Exception $e) {
            // noop
        }

        try {
            $connection = new $Driver();
            $this->assertTrue(false, 'Connection::initialize() expected throw exception without name option');
        } catch (\Exception $e) {
            // noop
        }
    }

    public function testOption()
    {
        $options = $this->connection->option();
        $this->assertTrue(is_array($options), 'Connection::option() expected array if no argument specified');

        $option = $this->connection->option('name');
        $this->assertEquals('default', $option, 'Connection::option() expected to get option for the name specified');
    }

    public function testGetName()
    {
        $name = $this->connection->getName();
        $this->assertEquals('default', $name, 'Connection::getName() expected to get name of connection');
    }

    public function testGetRaw()
    {
        $raw = $this->connection->getRaw();
        $this->assertNotNull($raw, 'Connection::getRaw() expected raw connection');
    }

    public function testSetRaw()
    {
        $this->connection->setRaw('something else');
        $raw = $this->connection->getRaw();
        $this->assertEquals('something else', $raw, 'Connection::setRaw() expected to modify raw connection');
    }

    public function testFactory()
    {
        $collection = $this->connection->factory('TestCollection');
        $msg = 'Connection::factory() expected to result Collection instance';
        $this->assertInstanceOf('Norm\\Collection', $collection, $msg);
    }

    public function testUnmarshall()
    {
        $uniqid = uniqid();
        $origin = array(
            'id' => $uniqid,
            'field_string' => 'Field String',
        );
        $result = $this->connection->unmarshall($origin);

        $message = 'Connection::unmarshall() expected the field [id] value is copied to field [$id].';
        $this->assertEquals($origin['id'], $result['$id'], $message);

        $message = 'Connection::unmarshall() expected the string-typed leave intact.';
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

        $message = 'Connection::marshall() expected the field [$id] removed.';
        $this->assertTrue(!isset($result['$id']), $message);
        $this->assertTrue(!isset($result['id']), $message);

        $message = 'Connection::marshall() expected the string-typed leave intact.';
        $this->assertEquals('Field String', $result['field_string'], $message);
        $this->assertTrue(is_string($result['field_string']), $message);
    }

    public function testQuery()
    {
        $collection = $this->connection->factory('TestCollection');

        $cursor = $this->connection->query($collection);

        $message = 'Connection::query() expected return value as '.$this->cursorClazz.'.';
        $this->assertInstanceOf($this->cursorClazz, $cursor, $message);
    }
}
