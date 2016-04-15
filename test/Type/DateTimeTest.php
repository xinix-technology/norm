<?php
namespace Norm\Test\Type;

use PHPUnit_Framework_TestCase;
use Norm\Type\DateTime;
use Norm\Repository;
use DateTimeZone;

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
        $this->assertEquals(date('H')+7, $dt->tzFormat('H', 'Asia/Jakarta'));
    }

    public function testLocalFormat()
    {
        $dt = $this->repository->resolve(DateTime::class);
        $this->assertEquals(date('H'), $dt->localFormat('H'));
    }

    public function testJsonSerializeToString()
    {
        $dt = $this->repository->resolve(DateTime::class);
        $this->assertEquals(date('c'), $dt->jsonSerialize());
        $this->repository->setAttribute('timezone', 'Asia/Jakarta');
        $this->assertEquals(strtotime($dt->jsonSerialize()), time());
    }

    public function testConstruct()
    {
        $dt = new DateTime($this->repository, '+1 Days', new DateTimeZone('Asia/Jakarta'));
        $this->assertEquals($dt->getTimeZone()->getName(), 'Asia/Jakarta');
    }
}
