<?php
namespace Norm\Test;

use Norm\Norm;
use Norm\Model;
use Norm\Collection;
use Norm\Connection;
use Norm\Schema\String;
use PHPUnit_Framework_TestCase;

class ModelTest extends PHPUnit_Framework_TestCase
{
    public function mockCollection()
    {
        $collection = $this->getMock(Collection::class, ['prepare'], [null, ['name' => 'Test']]);
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

    public function testCreateWithDefaultValue()
    {
        $connection = $this->getMock(Connection::class);
        $connection->method('persist')->will($this->returnCallback(function ($id, $model) {
            $model['id'] = 1;
            return $model;
        }));
        $norm = new Norm([
            'connections' => [
                'connection' => $connection,
            ]
        ]);

        $norm->addResolver(function ($id) {
            return [
                'schema' => [
                    'foo' => String::create()->withFilter('trim|default:bar|required'),
                ]
            ];
        });

        $model = $norm->factory('Foo')->newInstance();
        $this->assertNull($model['foo']);
        $model->save();
        $this->assertEquals('bar', $model['foo']);
    }
}
