<?php
namespace Norm\Test;

use Norm\Exception\NormException;
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
        $connection = $this->getMock(Connection::class);
        $this->repository = new Repository();
        $this->collection = new Collection($this->repository, $connection, ['name' => 'Foo']);
    }

    public function testSet()
    {
        $model = new Model($this->collection);
        try {
            $model->set('$id', 10);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Restricting model to set for $id.') {
                throw $e;
            }
        }
    }

    public function testClear()
    {
        $model = new Model($this->collection, [
            'foo' => 'foo',
            'bar' => 'bar',
        ]);
        $this->assertNotNull($model->clear('bar')['foo']);
        $this->assertNull($model->clear()['foo']);
        try {
            $model->clear('$id');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Restricting model to clear for $id.') {
                throw $e;
            }
        }

        unset($model->set('foo', 'foo')['foo']);
        $this->assertNull($model['foo']);
    }

    public function testFilter()
    {
        (new Model($this->collection))->filter();
    }

    public function testPrevious()
    {
        $model = new Model($this->collection, ['foo' => 'bar']);
        $this->assertEquals($model->previous(), ['foo' => 'bar']);
        $this->assertEquals($model->previous('foo'), 'bar');
    }

    public function testStatus()
    {
        $model = new Model($this->collection, ['foo' => 'bar']);
        $this->assertEquals($model->isRemoved(), false);
    }

    public function testToArrayAndDebugInfo()
    {
        $model = new Model($this->collection, ['foo' => 'bar']);
        $this->assertEquals($model->toArray(), $model->__debugInfo());
    }
}
