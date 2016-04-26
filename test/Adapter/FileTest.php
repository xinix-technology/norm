<?php
namespace Norm\Test\Adapter;

use Norm\Cursor;
use Norm\Collection;
use Norm\Adapter\File;
use ROH\Util\File as UtilFile;
use FilesystemIterator;
use Norm\Exception\NormException;
use PHPUnit_Framework_TestCase;

class FileTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        UtilFile::rm('tmp-db-files');
    }

    public function tearDown()
    {
        UtilFile::rm('tmp-db-files');
    }

    public function testConstruct()
    {
        try {
            $connection = new File(null, 'foo');
            $this->fail('Must not here');
        } catch(NormException $e) {}

        $connection = new File(null, 'foo', ['dataDir' => 'tmp-db-files']);
    }

    public function testPersist()
    {
        $connection = new File(null, 'foo', ['dataDir' => 'tmp-db-files']);
        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

        $result = $connection->persist('foo', ['foo' => 1]);
        $this->assertEquals($result['foo'], 1);
        $this->assertTrue(is_readable('tmp-db-files/foo/'. $result['$id'] .'.json'));

        $cursor = new Cursor($collection, ['$id' => $result['$id']]);
        $connection->remove($cursor);
        $this->assertFalse(is_readable('tmp-db-files/foo/'. $result['$id'] .'.json'));
    }

    public function testSize()
    {
        $connection = new File(null, 'foo', ['dataDir' => 'tmp-db-files']);
        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

        $result = $connection->persist('foo', ['foo' => 1]);
        $result = $connection->persist('foo', ['foo' => 2]);
        $result = $connection->persist('foo', ['foo' => 3]);

        $cursor = new Cursor($collection);
        $this->assertEquals($connection->size($cursor), 3);
    }

    public function testFetch()
    {
        $connection = new File(null, 'foo', ['dataDir' => 'tmp-db-files']);
        $collection = $this->getMock(Collection::class, null, [$connection, 'Foo']);

        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $this->assertEquals(count($connection->fetch(new Cursor($collection))), 3);

        UtilFile::rm('tmp-db-files');

        $this->assertEquals(count($connection->fetch(new Cursor($collection))), 0);

        $connection->persist('foo', ['foo' => 1]);
        $connection->persist('foo', ['foo' => 2]);
        $connection->persist('foo', ['foo' => 3]);

        $cursor = new Cursor($collection);
        $cursor->skip(1)->limit(1);
        $this->assertEquals(count($connection->fetch($cursor)), 1);

        $cursor = new Cursor($collection);
        $cursor->sort(['foo' => 1]);
        $this->assertEquals($connection->fetch($cursor)[0]['foo'], 3);

        $cursor = new Cursor($collection);
        $cursor->sort(['foo' => -1]);
        $this->assertEquals($connection->fetch($cursor)[0]['foo'], 1);
    }
}
