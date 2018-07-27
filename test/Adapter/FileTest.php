<?php
// namespace Norm\Test\Adapter;

// use Norm\Cursor;
// use Norm\Collection;
// use Norm\Adapter\File;
// use ROH\Util\File as UtilFile;
// use FilesystemIterator;
// use Norm\Exception\NormException;
// use PHPUnit\Framework\TestCase;
// use ROH\Util\Injector;
// use Norm\Repository;

// class FileTest extends TestCase
// {
//     public function setUp()
//     {
//         $this->injector = new Injector();
//         $this->injector->singleton(Repository::class, new Repository([], $this->injector));

//         UtilFile::rm('tmp-db-files');
//     }

//     public function tearDown()
//     {
//         UtilFile::rm('tmp-db-files');
//     }

//     public function testConstruct()
//     {
//         try {
//             $connection = $this->injector->resolve(File::class);
//             $this->fail('Must not here');
//         } catch (NormException $e) {
//         }

//         $connection = $this->injector->resolve(File::class, [
//             'id' => 'main',
//             'options' => ['dataDir' => 'tmp-db-files']
//         ]);
//     }

//     public function testPersist()
//     {
//         $connection = $this->injector->resolve(File::class, [
//             'id' => 'main',
//             'options' => ['dataDir' => 'tmp-db-files']
//         ]);
//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

//         $result = $connection->persist('foo', ['foo' => 1]);
//         $this->assertEquals($result['foo'], 1);
//         $this->assertTrue(is_readable('tmp-db-files/foo/' . $result['$id'] . '.json'));

//         $cursor = new Cursor($collection, ['$id' => $result['$id']]);
//         $connection->remove($cursor);
//         $this->assertFalse(is_readable('tmp-db-files/foo/' . $result['$id'] . '.json'));
//     }

//     public function testSize()
//     {
//         $connection = $this->injector->resolve(File::class, [
//             'id' => 'main',
//             'options' => ['dataDir' => 'tmp-db-files']
//         ]);
//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

//         $result = $connection->persist('foo', ['foo' => 1]);
//         $result = $connection->persist('foo', ['foo' => 2]);
//         $result = $connection->persist('foo', ['foo' => 3]);

//         $this->assertEquals($connection->size(new Cursor($collection)), 3);
//     }

//     public function testFetch()
//     {
//         $connection = $this->injector->resolve(File::class, [
//             'id' => 'main',
//             'options' => ['dataDir' => 'tmp-db-files']
//         ]);
//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $this->assertEquals($connection->read(new Cursor($collection))['foo'], 1);

//         UtilFile::rm('tmp-db-files');

//         $this->assertEquals($connection->read(new Cursor($collection)), null);

//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);

//         $cursor = new Cursor($collection);
//         $cursor->skip(1)->limit(1);
//         $this->assertEquals($connection->read($cursor)['foo'], 2);

//         $cursor = new Cursor($collection);
//         $cursor->sort(['foo' => 1]);
//         $this->assertEquals($connection->read($cursor)['foo'], 3);

//         $cursor = new Cursor($collection);
//         $cursor->sort(['foo' => -1]);
//         $this->assertEquals($connection->read($cursor)['foo'], 1);
//     }

//     public function testDistinct()
//     {
//         $connection = $this->injector->resolve(File::class, [
//             'id' => 'main',
//             'options' => ['dataDir' => 'tmp-db-files']
//         ]);
//         $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

//         $connection->persist('foo', ['foo' => 1]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 2]);
//         $connection->persist('foo', ['foo' => 3]);
//         $connection->persist('foo', ['foo' => 3]);
//         $connection->persist('foo', ['foo' => 3]);

//         $this->assertEquals($connection->distinct(new Cursor($collection), 'foo'), [1,2,3]);
//     }
// }
