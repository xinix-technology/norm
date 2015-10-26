<?php
namespace Norm\Test;

use Norm\Collection;
use Norm\Schema;
use Norm\Schema\Unknown;
use ROH\Util\Collection as UtilCollection;
use PHPUnit_Framework_TestCase;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $collection = new Collection(null, [
            'name' => 'Test'
        ]);

        $this->assertEquals('test', $collection->getId());

        $collection = new Collection(null, [
            'name' => 'Try',
            'id' => 'try',
        ]);

        $this->assertEquals('try', $collection->getId());
    }

    public function testGetSchema()
    {
        $collection = new Collection(null, [
            'name' => 'Test'
        ]);

        $this->assertInstanceOf(Schema::class, $collection->getSchema());
        $this->assertInstanceOf(Unknown::class, $collection->getSchema()['tryme']);
    }

    public function testWithSchema()
    {
        $collection = new Collection(null, [
            'name' => 'Test'
        ]);

        $result = $collection->withSchema([
            'foo' => $this->getMock(Unknown::class)
        ]);

        $this->assertEquals($collection, $result);
        $this->assertInstanceOf(Unknown::class, $collection->getSchema()['foo']);
    }

    public function testAttach()
    {
        $collection = new Collection(null, [
            'name' => 'Test',
        ]);

        $result = $collection->attach([
            'fname' => 'John',
            'lname' => 'Doe'
        ]);
    }
}
