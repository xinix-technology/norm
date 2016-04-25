<?php
namespace Norm\Test;

use Norm\Exception\NormException;
use Norm\Model;
use Norm\Collection;
use Norm\Connection;
use Norm\Repository;
use Norm\Schema\NField;
use PHPUnit_Framework_TestCase;

class ModelTest extends PHPUnit_Framework_TestCase
{
    // protected $repository;

    // public function setUp()
    // {
    //     $connection = $this->getMock(Connection::class);
    //     $this->repository = new Repository();
    //     $collection = new Collection($this->repository, $connection, ['name' => 'Foo']);
    // }

    public function testSetUnsetHas()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection);
        try {
            $model->set('$id', 10);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Restricting model to set for $id.') {
                throw $e;
            }
        }

        $model = new Model($collection, ['$id' => 1]);
        $this->assertEquals($model['$id'], 1);

        $model->set([
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        $this->assertEquals($model['foo'], 'bar');
        $this->assertEquals($model['bar'], 'baz');
        $model->set('foo', 'baz');
        $this->assertEquals($model['foo'], 'baz');
        $model['bar'] = 'foo';
        $this->assertEquals($model['bar'], 'foo');
        unset($model['bar']);
        $this->assertEquals($model['bar'], null);
        $this->assertFalse(isset($model['bar']));
        $this->assertTrue($model->has('foo'));

        $this->assertEquals($model->dump(), ['$id' => 1, 'foo' => 'baz']);
    }

    public function testReader()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, [
            '$id' => 1,
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        $schema = $model->getSchema();
        $fooField = $this->getMockForAbstractClass(NField::class, [$schema, 'foo']);
        $fooField->setReader(function() {
            return 'hijacked!';
        });
        $schema->addField($fooField);
        $this->assertEquals($model['foo'], 'hijacked!');
    }

    public function testClear()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, [
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
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        (new Model($collection))->filter();
    }

    public function testPrevious()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, ['foo' => 'bar']);
        $this->assertEquals($model->previous(), ['foo' => 'bar']);
        $this->assertEquals($model->previous('foo'), 'bar');
    }

    public function testStatus()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, ['foo' => 'bar']);
        $this->assertEquals($model->isRemoved(), false);
    }

    public function testToArrayAndDebugInfo()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, ['foo' => 'bar']);
        $this->assertEquals($model->toArray(), $model->__debugInfo());
    }

    public function testToArray()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, [
            '$id' => 1,
            '$hidden' => 'yes',
            'foo' => 'bar'
        ]);

        $this->assertEquals($model->toArray(), ['$id' => 1, '$hidden' => 'yes', 'foo' => 'bar', '$type' => 'Foo']);
        $this->assertEquals($model->toArray(Model::FETCH_ALL), ['$id' => 1, '$hidden' => 'yes', 'foo' => 'bar', '$type' => 'Foo']);
        $this->assertEquals($model->toArray(Model::FETCH_HIDDEN), ['$id' => 1, '$hidden' => 'yes', '$type' => 'Foo']);
        $this->assertEquals($model->toArray(Model::FETCH_PUBLISHED), ['foo' => 'bar']);
        $this->assertEquals($model->toArray(Model::FETCH_RAW), ['foo' => 'bar', '$hidden' => 'yes']);
    }

    public function testJsonSerialize()
    {
        $collection = $this->getMock(Collection::class, null, [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, [
            '$id' => 1,
            'foo' => 'bar'
        ]);

        $this->assertEquals($model->jsonSerialize()['foo'], 'bar');
    }

    public function testSaveAndRemove()
    {
        $collection = $this->getMock(Collection::class, ['save', 'remove'], [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $collection
            ->expects($this->once())
            ->method('save')
            ->will($this->returnCallback(function($model) {
                $model->sync($model->dump());
            }));
        $collection
            ->expects($this->once())
            ->method('remove')
            ->will($this->returnCallback(function($model) {
                $model->reset(true);
            }));
        $model = new Model($collection, [
            '$id' => 1,
            'foo' => 'bar'
        ]);
        $model->save();
        $this->assertFalse($model->isNew());

        $model->remove();
        $this->assertTrue($model->isRemoved());
    }

    public function testFormat()
    {
        $collection = $this->getMock(Collection::class, ['save', 'remove'], [
            $this->getMock(Connection::class),
            'Foo'
        ]);
        $model = new Model($collection, [
            '$id' => 1,
            'foo' => 'bar',
            'bar' => 'baz',
        ]);
        try {
            $model->format();
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Cannot format explicit schema fields') {
                throw $e;
            }
        }

        $field = $this->getMock(NField::class, null, [$model->getSchema(), 'foo']);
        $model->getSchema()->addField($field);
        $this->assertEquals($model->format(), 'bar');
        $this->assertEquals($model->format('plain'), 'bar');
        $this->assertEquals($model->format('plain', 'bar'), 'baz');
    }
}
