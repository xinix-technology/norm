<?php
// namespace Norm\Test\Adapter;

// use DateTime;
// use Norm\Cursor;
// use Norm\Collection;
// use Norm\Connection;
// use Norm\Adapter\Memory;
// use Norm\Exception\NormException;
// use PHPUnit\Framework\TestCase;
// use ROH\Util\Injector;
// use Norm\Repository;

// class MemoryTest extends TestCase
// {
//     public function setUp()
//     {
//         $this->injector = new Injector();
//         $this->injector->singleton(Repository::class, new Repository([], $this->injector));
//     }

//     public function testFetch()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);
//         $this->injector->singleton(Connection::class, $connection);

//         $collection = $this->injector->resolve(Collection::class, ['name' => 'Foo']);
//         $this->assertEquals($connection->read(new Cursor($collection, ['foo!lt' => 2]))['foo'], 1);
//         $this->assertEquals($connection->read(new Cursor($collection, ['foo!gt' => 2]))['foo'], 3);
//         $this->assertEquals($connection->read(new Cursor($collection, ['foo' => 2]))['foo'], 2);
//         $this->assertEquals($connection->read(new Cursor($collection, ['foo!eq' => 2]))['foo'], 2);
//         $this->assertEquals($connection->read(new Cursor($collection, ['foo!ne' => 1]))['foo'], 2);

//         $cursor = new Cursor($collection);
//         $cursor->skip(1)->limit(10);
//         $this->assertEquals($connection->read($cursor)['foo'], 2);

//         $cursor = new Cursor($collection);
//         $cursor->sort(['foo' => 1]);
//         $this->assertEquals($connection->read($cursor)['foo'], 3);


//         $this->assertEquals(
//             $connection->read(
//                 new Cursor($collection, [
//                     '!or' => [
//                         ['foo' => 1],
//                         ['foo' => 2]
//                     ],
//                 ])
//             )['foo'],
//             1
//         );

//         $this->assertEquals(
//             $connection->read(
//                 new Cursor($collection, [
//                     'foo!lte' => 2
//                 ])
//             )['foo'],
//             1
//         );

//         $this->assertEquals(
//             $connection->read(
//                 new Cursor($collection, [
//                     'foo!gte' => 2
//                 ])
//             )['foo'],
//             2
//         );

//         $this->assertEquals(
//             $connection->read(
//                 new Cursor($collection, [
//                     'foo!in' => [1,4,5]
//                 ])
//             )['foo'],
//             1
//         );

//         try {
//             $connection->read(
//                 new Cursor($collection, [
//                     'foo!oops' => 1
//                 ])
//             );
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//             if ($e->getMessage() !== "Operator 'oops' is not implemented yet!") {
//                 throw $e;
//             }
//         }
//     }

//     public function testGetContext()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $this->assertEquals(count($connection->getContext()['foo']), 3);
//     }

//     public function testSize()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
//         $cursor = new Cursor($collection);
//         $this->assertEquals($connection->size($cursor), 3);

//         $cursor->limit(1);
//         $this->assertEquals($connection->size($cursor, true), 1);
//     }

//     public function testRemoveAll()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
//         $connection->remove(new Cursor($collection));

//         $this->assertEquals(count($connection->getContext()['foo']), 0);
//     }

//     public function testRemovePartial()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1, 'bar' => 'bar']);
//         $connection->persist('foo', ['foo' => 2, 'bar' => 'bar']);
//         $connection->persist('foo', ['foo' => 3, 'bar' => 'baz']);

//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
//         $connection->remove(new Cursor($collection, ['bar' => 'bar']));

//         $this->assertEquals(count($connection->getContext()['foo']), 1);
//     }

//     public function testDistinct()
//     {
//         $connection = $this->injector->resolve(Memory::class);
//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);
//         $this->assertEquals(count($connection->distinct(new Cursor($collection), 'foo')), 3);
//     }
// }
