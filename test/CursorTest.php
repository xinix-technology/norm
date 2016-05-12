<?php
namespace Norm\Test;

use Norm\Cursor;
use Norm\Repository;
use Norm\Collection;
use Norm\Schema;
use Norm\Connection;
use Norm\Model;
use PHPUnit_Framework_TestCase;
use ROH\Util\Injector;

class CursorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
        $this->injector->singleton(Repository::class, $this->getMock(Repository::class));
        $this->injector->singleton(Connection::class, $this->getMockForAbstractClass(Connection::class, [$this->injector->resolve(Repository::class)]));
        $this->injector->singleton(Collection::class, $this->getMock(Collection::class, null, [ $this->injector->resolve(Connection::class), 'Foo' ]));
    }

    public function testGetCollectionAndCriteria()
    {
        $cursor = $this->injector->resolve(Cursor::class, [ 'criteria' => [ 'foo' => 'bar' ] ]);
        $this->assertEquals(['foo' => 'bar'], $cursor->getCriteria());
    }

    public function testLimit()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->limit(2)->getLimit(), 2);
    }

    public function testSort()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->sort(['foo' => 1])->getSort(), ['foo' => 1]);
    }

    public function testSkip()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->skip(2)->getSkip(), 2);
    }

    public function testMatch()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->match('foo')->getMatch(), 'foo');
    }

    public function testJsonSerialize()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->jsonSerialize(), []);
    }

    public function testFirst()
    {
        $collection = $this->getMock(Collection::class, ['read'], [$this->injector->resolve(Connection::class), 'Foo']);
        $collection->expects($this->once())->method('read');

        $cursor = $this->injector->resolve(Cursor::class, [
            'collection' => $collection
        ]);
        $cursor->first();
    }

    public function testCountAndSize()
    {
        $collection = $this->getMock(Collection::class, ['size'], [$this->injector->resolve(Connection::class), 'Foo']);
        $collection->expects($this->once())->method('size');

        $cursor = $this->injector->resolve(Cursor::class, [
            'collection' => $collection
        ]);

        $cursor->count();
    }

    public function testIteratorAccess()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $this->assertEquals($cursor->key(), 0);
        $cursor->next();
        $this->assertEquals($cursor->key(), 1);
        $cursor->prev();
        $this->assertEquals($cursor->key(), 0);
    }

    public function testToArray()
    {
        $collection = $this->getMock(Collection::class, ['read', 'getField'], [$this->injector->resolve(Connection::class), 'Foo']);
        $collection->method('read')->will($this->returnCallback(function($cursor) use ($collection) {
            if ($cursor->key() < 10) {
                return new Model($collection, ['foo' => 'bar'.$cursor->key()]);
            }
        }));
        $collection->method('getField')->will($this->returnValue(
            $this->getMockForAbstractClass(\Norm\Schema\NField::class, [$collection, 'foo']
        )));

        $cursor = $this->injector->resolve(Cursor::class, [
            'collection' => $collection
        ]);

        $this->assertEquals(count($cursor->toArray()), 10);
        $this->assertInstanceOf(Model::class, $cursor->toArray()[0]);
        $this->assertTrue(is_array($cursor->toArray(true)[0]));
    }

    public function testDistinct()
    {
        $collection = $this->getMock(Collection::class, ['distinct'], [$this->injector->resolve(Connection::class), 'Foo']);
        $collection->expects($this->once())->method('distinct');

        $cursor = $this->injector->resolve(Cursor::class, [
            'collection' => $collection
        ]);
        $cursor->distinct('foo');
    }

    public function testRemove()
    {
        $collection = $this->getMock(Collection::class, ['remove'], [$this->injector->resolve(Connection::class), 'Foo']);
        $collection->expects($this->once())->method('remove');

        $cursor = $this->injector->resolve(Cursor::class, [
            'collection' => $collection
        ]);
        $cursor->remove();
    }

    public function testDebugInfo()
    {
        $cursor = $this->injector->resolve(Cursor::class, [ 'criteria' => [ 'foo' => 'bar' ] ]);
        $cursor->skip(1)->limit(2)->sort(['foo' => Cursor::SORT_ASC]);

        $debugInfo = $cursor->__debugInfo();

        $this->assertEquals($debugInfo['criteria'], ['foo' => 'bar']);
        $this->assertEquals($debugInfo['skip'], 1);
        $this->assertEquals($debugInfo['limit'], 2);
        $this->assertEquals($debugInfo['sort'], ['foo' => 1]);
    }

    public function testGetAndSetContext()
    {
        $cursor = $this->injector->resolve(Cursor::class);
        $cursor->setContext('foo');
        $this->assertEquals($cursor->getContext(), 'foo');
    }
}
