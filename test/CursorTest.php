<?php
namespace Norm\Test;

use ArrayAccess;
use Norm\Cursor;
use Norm\Connection;
use Norm\Collection;
use PHPUnit_Framework_TestCase;

class CursorTest extends PHPUnit_Framework_TestCase
{
    // public function mockCollectionWithRows($rows)
    // {
    //     $collection = $this->getMock(
    //         Collection::class,
    //         [
    //             'cursorRead',
    //             'cursorFetch',
    //             'cursorSize',
    //             'cursorDistinct',
    //             'unmarshall',
    //         ],
    //         [
    //             $this->repository,
    //             $this->repository->getConnection(),
    //             ['name' => 'Test']
    //         ]
    //     );

    //     $collection->method('cursorRead')
    //         ->will($this->returnCallback(function ($context, $position) {
    //             if (isset($context[$position])) {
    //                 return $context[$position];
    //             }
    //         }));
    //     $collection->method('cursorFetch')
    //         ->will($this->returnCallback(function ($cursor) use ($rows) {
    //             return $rows;
    //         }));
    //     $collection->method('cursorDistinct')
    //         ->will($this->returnCallback(function ($cursor, $key) use ($rows) {
    //             $result = [];
    //             foreach ($rows as $k => $v) {
    //                 if (!in_array($v[$key], $result)) {
    //                     $result[] = $v[$key];
    //                 }
    //             }
    //             return $result;
    //         }));
    //     $collection->method('cursorSize')
    //         ->will($this->returnCallback(function ($cursor, $respectLimitSkip = false) use ($rows) {
    //             return count($rows);
    //         }));
    //     $collection->method('unmarshall')
    //         ->will($this->returnCallback(function ($doc) use ($rows) {
    //             return $doc;
    //         }));

    //     return $collection;
    // }

    // public function testGetCriteriaReturnArray()
    // {
    //     $collection = $this->mockCollectionWithRows([]);

    //     $cursor = new Cursor($collection);
    //     $this->assertEquals($cursor->getCriteria(), []);

    //     $cursor = new Cursor($collection, ['name' => 'John Doe']);
    //     $this->assertEquals('John Doe', $cursor->getCriteria()['name']);
    // }

    // public function testSetAndGetLimit()
    // {
    //     $collection = $this->mockCollectionWithRows([]);

    //     $cursor = new Cursor($collection);
    //     $this->assertEquals(-1, $cursor->getLimit(), 'default limit = 0');

    //     $retval = $cursor->limit(10);
    //     $this->assertEquals(10, $cursor->getLimit());
    //     $this->assertEquals($cursor, $retval);
    // }

    // public function testSetAndGetSkip()
    // {
    //     $collection = $this->mockCollectionWithRows([]);

    //     $cursor = new Cursor($collection);
    //     $this->assertEquals(0, $cursor->getSkip(), 'default skip = 0');

    //     $retval = $cursor->skip(10);
    //     $this->assertEquals(10, $cursor->getSkip());
    //     $this->assertEquals($cursor, $retval);
    // }

    // public function testSetAndGetSort()
    // {
    //     $collection = $this->mockCollectionWithRows([]);

    //     $cursor = new Cursor($collection);
    //     $this->assertEquals(0, $cursor->getSort(), 'default sort = 0');

    //     $sorts = [
    //         'name' => Cursor::SORT_ASC,
    //     ];
    //     $retval = $cursor->sort($sorts);
    //     $this->assertEquals($sorts, $cursor->getSort());
    //     $this->assertEquals($cursor, $retval);
    // }

    // public function testSetAndGetMatch()
    // {
    //     $collection = $this->mockCollectionWithRows([]);

    //     $cursor = new Cursor($collection);
    //     $this->assertNull($cursor->getMatch(), 'default match = null');

    //     $retval = $cursor->match('query');
    //     $this->assertEquals('query', $cursor->getMatch());
    //     $this->assertEquals($cursor, $retval);
    // }

    // public function testDistinct()
    // {
    //     $collection = $this->mockCollectionWithRows([
    //         ['fname' => 'John', 'lname' => 'Doe'],
    //         ['fname' => 'Jane', 'lname' => 'Doe'],
    //     ]);
    //     $cursor = new Cursor($collection);
    //     $result = $cursor->distinct('fname');
    //     $this->assertEquals(2, count($result));
    //     $result = $cursor->distinct('lname');
    //     $this->assertEquals(1, count($result));
    // }

    // public function testIteratorMethods()
    // {
    //     $collection = $this->mockCollectionWithRows([]);
    //     $cursor = new Cursor($collection);
    //     $this->assertFalse($cursor->valid());
    //     $this->assertNull($cursor->current());
    //     $this->assertEquals(0, $cursor->key());


    //     $collection = $this->mockCollectionWithRows([
    //         ['name' => 'john doe'],
    //         ['name' => 'jane doe'],
    //     ]);
    //     $cursor = new Cursor($collection);
    //     $this->assertEquals($cursor->current()['name'], 'john doe');
    //     $this->assertEquals(0, $cursor->key());
    //     $cursor->next();
    //     $this->assertTrue($cursor->valid());
    //     $this->assertEquals($cursor->current()['name'], 'jane doe');
    //     $this->assertEquals(1, $cursor->key());
    //     $cursor->next();
    //     $this->assertNull($cursor->current());
    //     $this->assertEquals(2, $cursor->key());
    //     $cursor->rewind();
    //     $this->assertEquals($cursor->current()['name'], 'john doe');
    //     $this->assertEquals(0, $cursor->key());
    // }

    // public function testCount()
    // {
    //     $collection = $this->mockCollectionWithRows([]);
    //     $cursor = new Cursor($collection);
    //     $this->assertEquals(0, $cursor->count());
    // }

    // public function testToArray()
    // {
    //     $row = $this->getMock(ArrayAccess::class, ['toArray', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset']);
    //     $rowArr = ['name' => 'john doe'];
    //     $row->method('toArray')
    //         ->willReturn($rowArr);

    //     $collection = $this->mockCollectionWithRows([
    //         $row,
    //     ]);
    //     $cursor = new Cursor($collection);

    //     $this->assertEquals($row, $cursor->toArray()[0]);
    //     $this->assertEquals($rowArr, $cursor->toArray(true)[0]);
    // }

    // public function testJsonSerialize()
    // {
    //     $collection = $this->mockCollectionWithRows([]);
    //     $cursor = new Cursor($collection);
    //     $this->assertTrue($cursor->toArray() === $cursor->jsonSerialize());
    // }
}
