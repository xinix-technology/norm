<?php
namespace Norm\Test;

use stdClass;
use Norm\Repository;
use Norm\Connection;
use Norm\Exception\NormException;
use PHPUnit_Framework_TestCase;
use Norm\Type\ArrayList;
use Norm\Type\Secret;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    protected $connection;

    public function setUp()
    {
        $this->repository = new Repository();
        $this->connection = $this->getMock(Connection::class, [
            'persist',
            'remove',
            'cursorDistinct',
            'cursorFetch',
            'cursorSize',
            'cursorRead',
        ]);
    }

    public function testConstruct()
    {
        try {
            $this->getMock(Connection::class, [], [88]);
            $this->fail('must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Connection must specified id') {
                throw $e;
            }
        }
    }

    public function testGetRaw()
    {
        $this->assertNull($this->connection->getRaw());
    }

    public function testUnmarshallAndMarshall()
    {
        try {
            $this->connection->unmarshall(new stdClass());
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Unmarshall only accept array or traversable') {
                throw $e;
            }
        }

        $arr = [
            'foo' => new ArrayList([ 2, 3, 4 ]),
            'bar' => new Secret('bar'),
        ];

        $marshalled = $this->connection->marshall($arr);
        $this->assertEquals($marshalled['foo'], '[2,3,4]');
        $this->assertEquals($marshalled['bar'], 'bar');
    }
}
