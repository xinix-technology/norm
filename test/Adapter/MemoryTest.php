<?php
namespace Norm\Test\Adapter;

use DateTime;
use Norm\Cursor;
use Norm\Collection;
use Norm\Adapter\Memory;
use Norm\Exception\NormException;
use PHPUnit_Framework_TestCase;

class MemoryTest extends PHPUnit_Framework_TestCase
{
    public function testFetch()
    {
        $connection = new Memory();
        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
        $this->assertEquals($connection->fetch(new Cursor($collection, ['foo!lt' => 2]))[0]['foo'], 1);
        $this->assertEquals($connection->fetch(new Cursor($collection, ['foo!gt' => 2]))[0]['foo'], 3);
        $this->assertEquals($connection->fetch(new Cursor($collection, ['foo' => 2]))[0]['foo'], 2);
        $this->assertEquals($connection->fetch(new Cursor($collection, ['foo!eq' => 2]))[0]['foo'], 2);
        $this->assertEquals(count($connection->fetch(new Cursor($collection, ['foo!ne' => 2]))), 2);

        $cursor = new Cursor($collection);
        $cursor->skip(1)->limit(10);
        $this->assertEquals(count($connection->fetch($cursor)), 2);

        $cursor = new Cursor($collection);
        $cursor->sort(['foo' => 1]);
        $this->assertEquals($connection->fetch($cursor)[0]['foo'], 3);


        $this->assertEquals(count(
            $connection->fetch(
                new Cursor($collection, [
                    '!or' => [
                        ['foo' => 1],
                        ['foo' => 2]
                    ],
                ])
            )
        ), 2);

        $this->assertEquals(count(
            $connection->fetch(
                new Cursor($collection, [
                    'foo!lte' => 2
                ])
            )
        ), 2);

        $this->assertEquals(count(
            $connection->fetch(
                new Cursor($collection, [
                    'foo!gte' => 2
                ])
            )
        ), 2);

        $this->assertEquals(count(
            $connection->fetch(
                new Cursor($collection, [
                    'foo!in' => [1,4,5]
                ])
            )
        ), 1);

        try {
            $connection->fetch(
                new Cursor($collection, [
                    'foo!oops' => 1
                ])
            );
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== "Operator 'oops' is not implemented yet!") {
                throw $e;
            }
        }
    }

    public function testGetRaw()
    {
        $connection = new Memory();
        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $this->assertEquals(count($connection->getRaw()['foo']), 3);
    }

    public function testSize()
    {
        $connection = new Memory();
        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
        $cursor = new Cursor($collection);
        $this->assertEquals($connection->size($cursor), 3);

        $cursor->limit(1);
        $this->assertEquals($connection->size($cursor, true), 1);
    }

    public function testRemove()
    {
        $connection = new Memory();
        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
        $connection->remove(new Cursor($collection));

        $this->assertEquals(count($connection->getRaw()['foo']), 0);
    }


    // protected $repository;

    // public function setUp()
    // {
    //     $this->repository = new Repository([
    //         'connections' => [
    //             [ Memory::class, [
    //                 'id' => 'memory',
    //             ]],
    //         ]
    //     ]);

    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
    //     $model->save();
    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
    //     $model->save();
    // }

    // public function testSearch()
    // {
    //     $cursor = $this->repository->factory('Foo')->find();

    //     $this->assertInstanceOf(Cursor::class, $cursor);
    // }

    // public function testCreate()
    // {
    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set([
    //         'fname' => 'John',
    //         'lname' => 'Doe',
    //     ]);
    //     $model->save();

    //     $this->assertEquals(
    //         $this->repository->getConnection('memory')->getContext()['foo'][$model['$id']]['id'],
    //         $model['$id']
    //     );
    // }

    // public function testRead()
    // {
    //     $this->testCreate();

    //     $model = $this->repository->factory('Foo')->findOne(['fname' => 'John']);
    //     $this->assertEquals('Doe', $model['lname']);

    //     $this->assertEquals(3, count($this->repository->getConnection('memory')->getContext()['foo']));
    // }

    // public function testUpdate()
    // {
    //     $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
    //     $model['fname'] = 'Rob';
    //     $model->save();

    //     $this->assertEquals('Rob', $this->repository->getConnection('memory')->getContext()['foo'][$model['$id']]['fname']);
    // }

    // public function testDelete()
    // {
    //     $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
    //     $model->remove();

    //     $this->assertEquals(1, count($this->repository->getConnection('memory')->getContext()['foo']));
    // }
}
