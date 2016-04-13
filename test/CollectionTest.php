<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;
use Norm\Observer\Timestampable;
use Norm\Exception\NormException;
// use Norm\Schema;
// use Norm\Schema\NUnknown;
// use ROH\Util\Collection as UtilCollection;
// use Norm\Test\NormTestCase;

class CollectionTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    public function setUp()
    {

        $connection = $this->getMock(Connection::class, [
            'persist',
            'remove',
            'cursorDistinct',
            'cursorFetch',
            'cursorSize',
            'cursorRead',
            'getId',
        ]);
        $connection->method('getId')->will($this->returnValue('main'));
        $this->repository = new Repository([
            'connections' => [
                $connection,
            ]
        ]);
        $this->repository->singleton(Connection::class, $connection);
    }

    public function testConstruct()
    {
        $hit = false;
        $collection = new Collection($this->repository, $this->repository->getConnection(), [
            'name' => 'Foo',
            'observers' => [
                [
                    'initialize' => function($context, $next) use (&$hit) {
                        $hit = true;
                        $next($context);
                    },
                ],
                [ Timestampable::class ],
            ]
        ]);

        $this->assertTrue($hit);
    }

    public function testObserve()
    {
        $collection = new Collection($this->repository, $this->repository->getConnection(), [
            'name' => 'Foo',
        ]);
        $collection->observe([
            'initialize' => function($context, $next) use (&$hit) {
                $hit = true;
                $next($context);
            }
        ]);

        $this->assertTrue($hit);

        try {
            $collection->observe(0);
            $this->fail('Must not here');
        } catch(NormException $e) {
            if ($e->getMessage() !== 'Observer must be array or object') {
                throw $e;
            }
        }
    }

    public function testDebugInfo()
    {
        $collection = new Collection($this->repository, $this->repository->getConnection(), [
            'name' => 'Foo',
        ]);
        $info = $collection->__debugInfo();
        $this->assertEquals($info['id'], 'foo');
        $this->assertEquals($info['name'], 'Foo');
    }

    public function testFactory()
    {
        $collection = new Collection($this->repository, $this->repository->getConnection(), [
            'name' => 'Foo',
        ]);

        $this->assertEquals($collection->factory('Foo'), $collection);
    }
}
