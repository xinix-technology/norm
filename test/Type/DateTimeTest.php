<?php
namespace Norm\Test\Type;

use PHPUnit\Framework\TestCase;
use Norm\Type\DateTime;
use Norm\Repository;
use DateTimeZone;

class DateTimeTest extends TestCase
{
    public function testFormat()
    {
        $dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

        $this->assertEquals((date('H')+7)%24, $dt->format('H'));
        $this->assertEquals(date('H'), $dt->serverFormat('H'));
    }

    public function testConstruct()
    {
        $dt = new DateTime();
        $this->assertEquals($dt->format('H'), $dt->serverFormat('H'));

        $this->assertEquals($dt->__debugInfo()['server'], date('c'));

        $dt = new DateTime('now');
        $this->assertEquals($dt->format('H'), $dt->serverFormat('H'));
    }

    public function testJsonSerializeOrToString()
    {
        $dt = new DateTime();
        $this->assertEquals(date('c'), $dt->jsonSerialize());
        $this->assertEquals(date('c'), $dt->__toString());
    }
}
