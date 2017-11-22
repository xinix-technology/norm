<?php
namespace Norm\Test\Adapter;

// use DateTime;
// use MongoId;
// use MongoConnectionException;
use Norm\Cursor;
use Norm\Connection;
use Norm\Collection;
use MongoClient;
use MongoDB;
use MongoDate;
use MongoId;
use MongoConnectionException;
use Norm\Adapter\Mongo;
use PHPUnit\Framework\TestCase;
use Norm\Exception\NormException;
use ROH\Util\Collection as UtilCollection;
use ROH\Util\Injector;

class MongoTest extends TestCase
{
    public function setUp()
    {
        if (!class_exists(MongoClient::class)) {
            $this->markTestSkipped('Mongo client not found.');
        }

        try {
            new MongoClient('mongodb://'.MongoClient::DEFAULT_HOST.':'.MongoClient::DEFAULT_PORT);
        } catch (MongoConnectionException $e) {
            $this->markTestSkipped('Mongo server is not available.');
        }

        $this->injector = new injector();
        $this->connection = $this->injector->resolve(Mongo::class, [
            'id' => 'name',
            'options' => [ 'database' => 'norm_mongo_test' ],
        ]);
        $this->injector->singleton(Connection::class, $this->connection);
        $context = $this->connection->getContext();
        $context->foo->remove();
        for($i = 0; $i < 3; $i++) {
            $context->foo->insert(['foo' => 100 + $i, 'bar' => 'bar-'.$i]);
        }
    }

    public function testConstruct()
    {
        $context = $this->connection->getContext();
        $this->assertInstanceOf(MongoDB::class, $context);

        try {
            $this->connection = $this->injector->resolve(Mongo::class, [
                'id' => 'main',
                'options' => [
                    'username' => 'foo',
                    'password' => 'foo',
                ]
            ]);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Unspecified database name') {
                throw $e;
            }
        }
    }

    public function testPersist()
    {
        $row = $this->connection->persist('foo', [
            'foo' => 'bar',
        ]);
        $this->assertEquals($this->connection->getContext()->foo->find(['foo' => 'bar'])->getNext()['foo'], 'bar');

        $row['bar'] = 'baz';
        $this->connection->persist('foo', $row);
        $this->assertEquals($this->connection->getContext()->foo->find(['foo' => 'bar'])->getNext()['bar'], 'baz');
    }

    public function testSize()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $this->assertEquals($this->connection->size($cursor), 3);
    }

    public function testDistinct()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $this->assertEquals($this->connection->distinct($cursor, 'foo'), [100, 101, 102]);
    }

    public function testRemove()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $this->connection->remove($cursor);
        $this->assertEquals($this->connection->getContext()->foo->find(['foo' => 101])->count(), 0);
    }

    public function testRead()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $row = $this->connection->read($cursor);
        $this->assertEquals($row['bar'], 'bar-0');

        $cursor->next();
        $row = $this->connection->read($cursor);
        $this->assertEquals($row['bar'], 'bar-1');

        $cursor->next();
        $row = $this->connection->read($cursor);
        $this->assertEquals($row['bar'], 'bar-2');

        $cursor->next();
        $row = $this->connection->read($cursor);
        $this->assertEquals($row, null);

        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $cursor->sort(['bar' => -1])
            ->skip(1)
            ->limit(1);
        $row = $this->connection->read($cursor);
        $this->assertEquals($row['bar'], 'bar-1');

        $cursor->next();
        $row = $this->connection->read($cursor);
        $this->assertEquals($row, null);
    }

    public function testMarshall()
    {
        $t = new \DateTime();
        $c = new UtilCollection(['foo' => 'bar']);
        $cursor = new Cursor($this->injector->resolve(Collection::class, ['name' => 'Foo']), ['criteria' => ['foo' => 101]]);
        $result = $this->connection->marshall([
            't' => $t,
            'c' => $c,
        ]);
        $this->assertInstanceOf(\MongoDate::class, $result['t']);
        $this->assertEquals($result['c'], ['foo' => 'bar']);
    }

    public function testUnmarshall()
    {
        $cursor = new Cursor($this->injector->resolve(Collection::class, ['name' => 'Foo']), ['criteria' => ['foo' => 101]]);
        $t = time();
        $id = new MongoId();
        $result = $this->connection->unmarshall([
            't' => new MongoDate($t),
            'i' => $id,
        ]);
        $this->assertEquals($result['t']->getTimestamp(), $t);
        $this->assertEquals($result['i'], $id.'');
    }
}
