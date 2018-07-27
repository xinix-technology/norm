<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Exception\NormException;
use Norm\Schema\NReference;
use Norm\Repository;
use Norm\Collection;
use Norm\Cursor;
use Norm\Connection;
use ROH\Util\Injector;

class NReferenceTest extends AbstractTest
{
    public function testConstructRejected()
    {
        try {
            $field = new NReference($this->injector->resolve(Collection::class), 'foo', 10);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Foreign must be instance of string, array, callable or Collection') {
                throw $e;
            }
        }
    }

    public function testConstructToCollection()
    {
        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => 'Foo']);
        $this->assertEquals($field['to$collection'], 'Foo');
        $this->assertEquals($field['to$key'], '$id');
        $this->assertEquals($field['to$label'], '');
    }

    public function testFetch()
    {
        $this->markTestSkipped('Skipped');
        $repository = $this->getMock(Repository::class, ['factory']);
        $repository->method('factory')->will($this->returnCallback(function () {
            return $this->collection;
        }));
        $this->injector->singleton(Repository::class, $repository);

        $this->collection = $this->getMock(Collection::class, ['find'], [$this->injector->resolve(Connection::class), 'Foo']);
        $cursor = $this->getMock(Cursor::class, ['toArray', 'first'], [$this->collection]);
        $cursor->method('toArray')->will($this->returnValue([
            ['$id' => 1, 'name' => 'foo'],
            ['$id' => 2, 'name' => 'bar'],
            ['$id' => 3, 'name' => 'baz'],
        ]));
        $cursor->method('first')->will($this->returnValue(['$id' => 3, 'name' => 'baz']));

        $this->collection->expects($this->exactly(1))
            ->method('find')
            ->with(null)
            ->will($this->returnValue($cursor));

        $field = $this->injector->resolve(NReference::class, [
            'name' => 'foo',
            'to' => 'Foo:$id,name'
        ]);
        $this->assertEquals($field['to$collection'], 'Foo');
        $this->assertEquals($field['to$key'], '$id');
        $this->assertEquals($field['to$label'], 'name');

        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch(3)['name'], 'baz');
    }

    public function testConstructCollectionWithCriteriaAndSort()
    {
        $this->markTestSkipped('Skipped');
        $repository = $this->getMock(Repository::class, ['factory']);
        $repository->method('factory')->will($this->returnCallback(function () {
            return $this->collection;
        }));
        $this->injector->singleton(Repository::class, $repository);

        $isFetchId = false;
        $this->collection = $this->getMock(Collection::class, ['find'], [$this->injector->resolve(Connection::class), 'Foo']);
        $this->collection->expects($this->exactly(2))
            ->method('find')
            ->with(['age' => 20])
            ->will($this->returnCallback(function ($criteria) use (&$isFetchId) {
                $cursor = $this->getMock(Cursor::class, ['sort', 'first'], [$this->collection, $criteria]);
                $cursor->expects($this->exactly(1))->method('sort')->with(['name' => 1]);
                if ($isFetchId) {
                    $cursor->expects($this->exactly(1))->method('first');
                }
                return $cursor;
            }));

        $field = $this->injector->resolve(NReference::class, [
            'name' => 'foo',
            'to' => 'Foo?!sort[name]=1&age=20',
            'filter' => null,
            'format' => [],
            'attributes' => ['nocache' => true],
        ]);
        $this->assertEquals($field['to$sort'], ['name' => 1]);
        $this->assertEquals($field['to$criteria'], ['age' => 20]);

        $field->fetch();
        $isFetchId = true;
        $field->fetch(1);
    }

    public function testConstructToCollectionNoCache()
    {
        $hit = 0;
        $field = $this->injector->resolve(NReference::class, [
            'name' => 'foo',
            'to' => function () use (&$hit) {
                $hit++;
            },
            'filter' => null,
            'format' => [],
            'attributes' => ['nocache' => true]
        ]);
        $field->fetch();
        $field->fetch();
        $this->assertEquals($hit, 2);
    }

    public function testConstructToArray()
    {
        $options = [
            'foo' => 'Foo',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ];
        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => $options]);
        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch('bar'), 'Bar');
        $this->assertEquals($field->fetch()['foo'], 'Foo');
    }

    public function testConstructToCallable()
    {
        $options = function () {
            return [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'baz' => 'Baz',
            ];
        };
        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => $options]);
        $this->assertEquals(count($field->fetch()), 3);
        $this->assertEquals($field->fetch('bar'), 'Bar');
        $this->assertEquals($field->fetch()['foo'], 'Foo');
    }

    public function testPrepare()
    {
        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => 'Foo']);
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

        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => 'Foo:code']);
        $this->assertEquals($field->prepare(10), 10);
        $this->assertEquals($field->prepare(['code' => 10]), 10);

        $field = $this->injector->resolve(NReference::class, ['name' => 'foo', 'to' => 'Foo']);
    }

    public function testFormat()
    {
        $this->markTestSkipped('Skipped');
        $field = $this->getMock(NReference::class, ['fetch'], [
            $this->injector->resolve(Collection::class),
            'foo',
            'Foo:$id,name',
            null,
            [],
            'attributes' => [
                'nocache' => true,
            ]
        ]);
        $field->method('fetch')->will($this->returnCallback(function ($key) {
            return [
                '$id' => 1,
                'name' => 'foo',
            ];
        }));
        $this->assertEquals($field->format('json', 1), 1);
        $this->assertEquals($field->format('json', 1, ['include' => true]), ['$id' => 1, 'name' => 'foo']);
        $this->assertEquals($field->format('plain', 1), 'foo');
        $this->assertEquals($field->format('input', 1), '__norm__/nreference/input');

        $field = $this->injector->resolve(NReference::class, [
            'name' => 'foo',
            'to' => [
                1 => 'foo',
            ],
        ]);
        $this->assertEquals($field->format('json', 1), 1);
        $this->assertEquals($field->format('json', 1, ['include' => true]), 1);
        $this->assertEquals($field->format('plain', 1), 'foo');
    }
}
