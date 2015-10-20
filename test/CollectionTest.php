<?php
namespace Norm\Test;

use Norm\Collection;
use Norm\Type\Object;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $collection = new Collection([
            'name' => 'Test'
        ]);

        $this->assertEquals('test', $collection->getId());

        $collection = new Collection([
            'name' => 'Try',
            'id' => 'try',
        ]);

        $this->assertEquals('try', $collection->getId());
    }

    // public function testGetConnection()
    // {
    //     $connection = new \stdClass();
    //     $collection = new Collection([
    //         'name' => 'Test',
    //         'connection' => $connection
    //     ]);

    //     $this->assertEquals($connection, $collection->getConnection());
    // }

    public function testGetSchema()
    {
        $collection = new Collection([
            'name' => 'Test'
        ]);

        $this->assertInstanceOf(Object::class, $collection->getSchema());
        $this->assertEquals(null, $collection->getSchema('tryme'));
    }

    public function testWithSchema()
    {
        $collection = new Collection([
            'name' => 'Test'
        ]);

        $result = $collection->withSchema([
            'foo' => 'bar'
        ]);

        $this->assertEquals($collection, $result);
        $this->assertEquals('bar', $collection->getSchema('foo'));
    }

    public function testPrepare()
    {
        $collection = new Collection([
            'name' => 'Test'
        ]);

        $result = $collection->prepare('key', 'value');
        $this->assertEquals('value', $result);
    }

    public function testAttach()
    {
        $collection = new Collection([
            'name' => 'Test',
        ]);

        $result = $collection->attach([
            'fname' => 'John',
            'lname' => 'Doe'
        ]);

        // var_dump($result);
    }
}
