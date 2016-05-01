<?php
namespace Norm\Test;

use Norm\Cursor;
use Norm\Repository;
use Norm\Collection;
use Norm\Schema;
use Norm\Connection;
use Norm\Model;
use PHPUnit_Framework_TestCase;

class CursorTest extends PHPUnit_Framework_TestCase
{
    public function testGetCollectionAndCriteria()
    {
        $collection = new Collection(null, 'Foo');
        $cursor = new Cursor($collection, ['foo' => 'bar']);
        $this->assertEquals($collection, $cursor->getCollection());
        $this->assertEquals(['foo' => 'bar'], $cursor->getCriteria());
    }

    public function testLimit()
    {
        $collection = new Collection(null, 'Foo');
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->limit(2)->getLimit(), 2);
    }

    public function testSort()
    {
        $collection = new Collection(null, 'Foo');
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->sort(['foo' => 1])->getSort(), ['foo' => 1]);
    }

    public function testSkip()
    {
        $collection = new Collection(null, 'Foo');
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->skip(2)->getSkip(), 2);
    }

    public function testMatch()
    {
        $collection = new Collection(null, 'Foo');
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->match('foo')->getMatch(), 'foo');
    }

    public function testJsonSerialize()
    {
        $collection = $this->getMock(Collection::class, [], [ null, 'Foo' ]);
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->jsonSerialize(), []);
    }

    public function testFirst()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);
        $collection->expects($this->once())->method('read');
        $cursor = new Cursor($collection);
        $cursor->first();
    }

    public function testCountAndSize()
    {
        $collection = $this->getMock(Collection::class, ['size'], [null, 'Foo']);
        $collection->expects($this->once())->method('size');
        $cursor = new Cursor($collection);
        $cursor->count();
    }

    public function testIteratorAccess()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);
        $collection->expects($this->once())->method('read')->will($this->returnValue(['foo' => 'bar']));
        $cursor = new Cursor($collection);
        $this->assertEquals($cursor->key(), 0);
        $cursor->next();
        $this->assertEquals($cursor->key(), 1);
        $cursor->prev();
        $this->assertEquals($cursor->key(), 0);
    }

    public function testToArray()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);
        $collection->method('read')->will($this->returnCallback(function($cursor) use ($collection) {
            if ($cursor->key() < 10) {
                return new Model($collection, ['foo' => 'bar'.$cursor->key()]);
            }
        }));
        $schema = new Schema($collection);
        $collection->method('getSchema')->will($this->returnValue($schema));
        $cursor = new Cursor($collection);
        $this->assertEquals(count($cursor->toArray()), 10);
        $this->assertInstanceOf(Model::class, $cursor->toArray()[0]);
        $this->assertTrue(is_array($cursor->toArray(true)[0]));
    }

    public function testDistinct()
    {
        $collection = $this->getMock(Collection::class, ['distinct'], [null, 'Foo']);
        $collection->expects($this->once())->method('distinct');
        $cursor = new Cursor($collection);
        $cursor->distinct('foo');
    }

    public function testRemove()
    {
        $collection = $this->getMock(Collection::class, ['remove'], [null, 'Foo']);
        $collection->expects($this->once())->method('remove');
        $cursor = new Cursor($collection);
        $cursor->remove();
    }

    public function testDebugInfo()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [null, 'Foo']), ['foo' => 'bar']);
        $cursor->skip(1)->limit(2)->sort(['foo' => Cursor::SORT_ASC]);
        $this->assertEquals($cursor->__debugInfo()['criteria'], ['foo' => 'bar']);
        $this->assertEquals($cursor->__debugInfo()['skip'], 1);
        $this->assertEquals($cursor->__debugInfo()['limit'], 2);
        $this->assertEquals($cursor->__debugInfo()['sort'], ['foo' => 1]);
    }
}
