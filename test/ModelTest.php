<?php
namespace Norm\Test;

use Norm\Model;
use Norm\Collection;
use Norm\Connection;
use Norm\Repository;
use Norm\Schema\NString;
use PHPUnit_Framework_TestCase;

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    public function setUp()
    {
        $this->repository = new Repository();
    }
    // public function getCollection()
    // {
    //     $repository = new Repository();
    //     $connection = $this->getMock(Connection::class, [], ['default']);
    //     $collection = $repository->resolve(Collection::class, [
    //         'connection' => $connection,
    //         'options' => [
    //             'name' => 'test',
    //         ]
    //     ]);
    //     return $collection;
    // }

    // public function testGetId()
    // {
    //     $collection = $this->getCollection();
    //     $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
    //     $this->assertEquals(10, $model->getId());
    // }

    // public function testHas()
    // {
    //     $collection = $this->getCollection();
    //     $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
    //     $this->assertTrue($model->has('name'));
    //     $this->assertFalse($model->has('address'));
    // }

    // public function testGet()
    // {
    //     $collection = $this->getCollection();
    //     $model = new Model($collection, ['$id' => 10, 'name' => 'John Doe']);
    //     $this->assertEquals('John Doe', $model->get('name'));
    // }

    // public function testCreateWithDefaultValue()
    // {
    //     $connection = $this->getMock(Connection::class, [], ['connection']);
    //     $connection->method('persist')->will($this->returnCallback(function ($id, $model) {
    //         $model['id'] = 1;
    //         return $model;
    //     }));
    //     $repository = new Repository([
    //         'connections' => [
    //             $connection,
    //         ]
    //     ]);

    //     $repository->addResolver(function ($id) {
    //         return [
    //             'schema' => [
    //                 [ NString::class, [
    //                     'options' => [
    //                         'name' => 'foo',
    //                         'filter' => 'trim|default:bar|required',
    //                     ],
    //                 ]],
    //             ],
    //         ];
    //     });

    //     $model = $repository->factory('Foo')->newInstance();
    //     $this->assertNull($model['foo']);
    //     $model->save();
    //     $this->assertEquals('bar', $model['foo']);
    // }
}
