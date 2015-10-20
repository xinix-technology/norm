<?php
namespace Norm\Test;

use Norm\Model;
use Norm\Collection;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function mockCollection()
    {
        $collection = $this->getMock(Collection::class, ['prepare'], [['name' => 'Test']]);
        $collection->method('prepare')->will($this->returnCallback(function ($key, $value) {
            return $value;
        }));
        return $collection;
    }

    public function testGetId()
    {
        $collection = $this->mockCollection();
        $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
        $this->assertEquals(10, $model->getId());
    }

    public function testHas()
    {
        $collection = $this->mockCollection();
        $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
        $this->assertTrue($model->has('name'));
        $this->assertFalse($model->has('address'));
    }

    public function testGet()
    {
        $collection = $this->mockCollection();
        $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
        $this->assertEquals('John Doe', $model->get('name'));
    }
}
