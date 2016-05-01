<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Connection;
use Norm\Collection;
use Norm\Cursor;
use Norm\Model;
use Norm\Exception\NormException;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstructAndObserve()
    {
        $collection = new Collection(null, 'Foo');
        $this->assertEquals($collection->getId(), 'foo');
        $this->assertEquals($collection->getName(), 'Foo');

        $collection = new Collection(null, ['Foo', 'bar']);
        $this->assertEquals($collection->getId(), 'bar');
        $this->assertEquals($collection->getName(), 'Foo');

        try {
            $collection = new Collection(null, 11);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Collection name must be string') {
                throw $e;
            }
        }
    }

    public function testObserve()
    {
        $collection = new Collection(null, 'Foo');
        $collection->observe([
            'initialize' => function($context) use (&$hit) {
                $hit = true;
            },
            'save' => function($context, $next) {},
        ]);
        $this->assertTrue($hit);

        $collection = new Collection(null, 'Foo');
        $observer = $this->getMock(stdClass::class, ['initialize', 'save']);
        $observer->expects($this->once())->method('initialize');
        $collection->observe($observer);

        try {
            $collection->observe(0);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Observer must be array or object') {
                throw $e;
            }
        }
    }

    public function testDebugInfoAndGetters()
    {
        $collection = new Collection(null, 'Foo');
        $info = $collection->__debugInfo();
        $this->assertEquals($info['id'], 'foo');
        $this->assertEquals($info['name'], 'Foo');

        $this->assertEquals($collection->getId(), 'foo');
        $this->assertEquals($collection->getName(), 'Foo');
    }

    public function testAttach()
    {
        $collection = new Collection(null, 'Foo');
        $result = $collection->attach([
            '$id' => 1,
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $this->assertInstanceOf(Model::class, $result);
        $this->assertEquals($result['foo'], 'bar');
    }

    public function testFindAndFindOne()
    {
        $connection = $this->getMock(Connection::class);
        $connection->method('read')->will($this->returnValue(['foo' => 'bar']));
        $collection = new Collection($connection, 'Foo');

        $result = $collection->find();
        $this->assertInstanceOf(Cursor::class, $result);

        $result = $collection->findOne(10);
        $this->assertInstanceOf(Model::class, $result);
    }

    public function testNewInstanceSaveAndRemove()
    {
        $connection = $this->getMock(Connection::class);
        $connection->method('persist')->will($this->returnValue(['$id' => 1, 'foo' => 'bar']));
        $connection->expects($this->once())->method('remove');
        $collection = new Collection($connection, 'Foo');

        $model = $collection->newInstance();
        $this->assertInstanceOf(Model::class, $model);

        $model->set('foo', 'bar');
        $collection->save($model);
        $this->assertFalse($model->isNew());

        $collection->save($model, ['observer' => false]);
        $this->assertFalse($model->isNew());

        $collection->remove($model);

        $connection = $this->getMock(Connection::class);
        $connection->expects($this->once())->method('remove');
        $collection = new Collection($connection, 'Foo');
        $model = $collection->newInstance();
        $collection->remove($model, ['observer' => false]);

        $connection = $this->getMock(Connection::class);
        $connection->expects($this->once())->method('remove');
        $collection = new Collection($connection, 'Foo');
        $collection->remove();

        $connection = $this->getMock(Connection::class);
        $connection->expects($this->once())->method('remove');
        $collection = new Collection($connection, 'Foo');
        $cursor = $collection->find(['foo' => 'bar']);
        $collection->remove($cursor);
    }

    public function testDelegateCursorMethods()
    {
        $connection = $this->getMock(Connection::class);
        $connection->expects($this->once())->method('distinct');
        $connection->expects($this->once())->method('size');
        $connection->expects($this->once())->method('read');
        $collection = new Collection($connection, 'Foo');

        $cursor = new Cursor($collection);

        $collection->distinct($cursor, 'foo');
        $collection->size($cursor);
        $collection->read($cursor);
    }
}
