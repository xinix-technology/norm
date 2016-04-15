<?php
namespace Norm\Test;

use Norm\Cursor;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use PHPUnit_Framework_TestCase;

class CursorTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    protected $cursor;

    public function setUp()
    {
        $this->repository = new Repository();
        $connection = $this->getMock(Connection::class);
        $collection = $this->getMock(Collection::class, [], [
            $this->repository,
            $connection,
            [ 'name' => 'Foo' ]
        ]);

        $this->cursor = new Cursor($collection);
    }

    public function testSkip()
    {
        $this->assertEquals($this->cursor->skip(2)->getSkip(), 2);
    }

    public function testMatch()
    {
        $this->assertEquals($this->cursor->match('foo')->getMatch(), 'foo');
    }

    public function testJsonSerialize()
    {
        $this->assertEquals($this->cursor->jsonSerialize(), []);
    }
}
