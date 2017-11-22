<?php
namespace Norm\Test;

use stdClass;
use Norm\Repository;
use Norm\Connection;
use Norm\Exception\NormException;
use PHPUnit\Framework\TestCase;
use Norm\Type\ArrayList;
use Norm\Type\Secret;
use ROH\Util\Injector;

class ConnectionTest extends TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
        $this->repository = new Repository([], $this->injector);
        $this->injector->singleton(Repository::class, $this->repository);
    }

    public function testMarshallKV()
    {
        $this->assertEquals($this->getMockForAbstractClass(Connection::class, [$this->repository])->marshallKV('$id', 10), ['id', 10]);
    }

    public function testUnmarshallAndMarshall()
    {
        $connection = $this->getMockForAbstractClass(Connection::class, [$this->repository], '', true, true, true, ['getAttribute']);
        $connection->method('getAttribute')->will($this->returnValue('Asia/Jakarta'));

        $arr = [
            'foo' => new ArrayList([ 2, 3, 4 ]),
            'bar' => new Secret('bar'),
        ];

        $marshalled = $connection->marshall($arr);
        $this->assertEquals($marshalled['foo'], '[2,3,4]');
        $this->assertEquals($marshalled['bar'], 'bar');

        $now = new \Norm\Type\DateTime('now', 'Asia/Jakarta');
        $marshalled = $connection->marshall([
            '$id' => 10,
            '$hidden' => true,
            'dt' => $now,
        ]);

        $this->assertFalse(isset($marshalled['$id']));
        $this->assertEquals($marshalled['_hidden'], true);
        $this->assertEquals($marshalled['dt'], $now->format('c'));

        $unmarshalled = $connection->unmarshall([
            'id' => 10,
            '_hidden' => true,
            'foo' => 'bar',
        ]);
        $this->assertEquals($unmarshalled['foo'], 'bar');
        $this->assertEquals($unmarshalled['$id'], 10);
        $this->assertEquals($unmarshalled['$hidden'], true);
    }

    public function testMarshalCriteria()
    {
        $connection = $this->getMockForAbstractClass(Connection::class, [$this->repository]);

        $this->assertEquals($connection->marshallCriteria(['$foo' => 'bar']), ['_foo' => 'bar']);
    }
}
