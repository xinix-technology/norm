<?php
namespace Norm\Test\Type;

use PHPUnit_Framework_TestCase;
use Norm\Type\DateTime;
use Norm\Repository;

class DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->repository = new Repository();
    }

    public function testTzFormat()
    {
        $dt = $this->repository->resolve(DateTime::class);

        $this->assertEquals(date('H'), $dt->tzFormat('H'));
    }

    public function testLocalFormat()
    {
        $dt = $this->repository->resolve(DateTime::class);
        $this->assertEquals(date('H'), $dt->localFormat('H'));
    }
}
