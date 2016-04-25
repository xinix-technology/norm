<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NReference;
use Norm\Schema;
use Norm\Collection;
use Norm\Cursor;
use Norm\Exception\NormException;

class NReferenceTest extends PHPUnit_Framework_TestCase
{
    public function testConstructRejected()
    {
        try {
            $field = new NReference(null, 'foo');
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Foreign must be instance of string, array, callable or Collection') {
                throw $e;
            }
        }
    }

    public function testConstructToCollection()
    {
        $field = new NReference(null, 'foo', null, 'Foo');
        $this->assertEquals($field['to$collection'], 'Foo');
        $this->assertEquals($field['to$key'], '$id');
        $this->assertEquals($field['to$label'], '');

        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);

        $cursor = $this->getMock(Cursor::class, ['toArray', 'first'], [$collection]);
        $cursor->method('toArray')->will($this->returnValue([
            ['$id' => 1, 'name' => 'foo'],
            ['$id' => 2, 'name' => 'bar'],
            ['$id' => 3, 'name' => 'baz'],
        ]));
        $cursor->method('first')->will($this->returnValue(['$id' => 3, 'name' => 'baz']));
        $collection->expects($this->exactly(1))
            ->method('find')
            ->with(null)
            ->will($this->returnValue($cursor));

        $schema = $this->getMock(Schema::class);
        $schema->expects($this->exactly(1))->method('factory')->will($this->returnValue($collection));

        $field = new NReference($schema, 'foo', null, 'Foo:$id,name');
        $this->assertEquals($field['to$collection'], 'Foo');
        $this->assertEquals($field['to$key'], '$id');
        $this->assertEquals($field['to$label'], 'name');

        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch(3)['name'], 'baz');
    }

    public function testConstructCollectionWithCriteriaAndSort()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);

        $cursor = new Cursor($collection, ['age' => 20]);

        $collection->expects($this->exactly(1))
            ->method('find')
            ->with(['age' => 20])
            ->will($this->returnValue($cursor));

        $schema = $this->getMock(Schema::class);
        $schema->expects($this->once())->method('factory')->will($this->returnValue($collection));

        $field = new NReference($schema, 'foo', null, 'Foo?!sort[name]=1&age=20');
        $this->assertEquals($field['to$sort'], ['name' => 1]);
        $this->assertEquals($field['to$criteria'], ['age' => 20]);

        $field->fetch();
    }

    public function testConstructToCollectionNoCache()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);

        $cursor = $this->getMock(Cursor::class, ['toArray', 'first'], [$collection]);
        $cursor->method('toArray')->will($this->returnValue([
            ['$id' => 1, 'name' => 'foo'],
            ['$id' => 2, 'name' => 'bar'],
            ['$id' => 3, 'name' => 'baz'],
        ]));
        $cursor->method('first')->will($this->returnValue(['$id' => 3, 'name' => 'baz']));

        $collection->expects($this->exactly(3))
            ->method('find')
            ->will($this->returnValue($cursor));

        $schema = $this->getMock(Schema::class);
        $schema->expects($this->exactly(3))->method('factory')->will($this->returnValue($collection));

        $field = new NReference($schema, 'foo', null, 'Foo:$id,name', ['nocache' => true]);
        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch()[2]['name'], 'bar');
        $this->assertEquals($field->fetch(3)['name'], 'baz');
    }

    public function testConstructToArray()
    {
        $options = [
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ];
        $field = new NReference(null, 'foo', null, $options);
        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch('bar'), 'Bar');
        $this->assertEquals($field->fetch()['foo'], 'Foo');
    }

    public function testConstructToCallable()
    {
        $options = function() {
            return [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'baz' => 'Baz',
            ];
        };
        $field = new NReference(null, 'foo', null, $options);
        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch('bar'), 'Bar');
        $this->assertEquals($field->fetch()['foo'], 'Foo');
    }

    public function testPrepare()
    {
        $field = new NReference(null, 'foo', null, 'Foo');
        $this->assertEquals($field->prepare(10), 10);
        $this->assertEquals($field->prepare(['$id' => 10]), 10);
        try {
            $this->assertEquals($field->prepare(['code' => 10]), 10);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Unable to get reference key from value') {
                throw $e;
            }
        }

        $field = new NReference(null, 'foo', null, 'Foo:code');
        $this->assertEquals($field->prepare(10), 10);
        $this->assertEquals($field->prepare(['code' => 10]), 10);

        $field = new NReference(null, 'foo', null, 'Foo');
    }

    public function testFormat()
    {
        $collection = $this->getMock(Collection::class, [], [null, 'Foo']);

        $cursor = $this->getMock(Cursor::class, ['toArray', 'first'], [$collection]);
        $cursor->method('toArray')->will($this->returnValue([
            ['$id' => 1, 'name' => 'foo'],
            ['$id' => 2, 'name' => 'bar'],
            ['$id' => 3, 'name' => 'baz'],
        ]));
        $cursor->method('first')->will($this->returnValue(['$id' => 3, 'name' => 'baz']));
        $collection->expects($this->exactly(1))
            ->method('find')
            ->with(null)
            ->will($this->returnValue($cursor));

        $schema = $this->getMock(Schema::class);
        $schema->expects($this->exactly(1))->method('factory')->will($this->returnValue($collection));
        $schema->method('render')->will($this->returnCallback(function($t) { return $t; }));

        $field = new NReference($schema, 'foo', null, 'Foo:$id,name');
        $this->assertEquals($field->format('json', 1), 1);
        $this->assertEquals($field->format('json', 1, ['include' => true]), ['$id' => 1, 'name' => 'foo']);
        $this->assertEquals($field->format('plain', 1), 'foo');

        $field = new NReference($schema, 'foo', null, ['foo' => 'Foo']);
        $this->assertEquals($field->format('json', 'foo'), 'foo');
        $this->assertEquals($field->format('plain', 'foo'), 'Foo');
        $this->assertEquals($field->format('input', 'foo'), '__norm__/nreference/input');
    }
}