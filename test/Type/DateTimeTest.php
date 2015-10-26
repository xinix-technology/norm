<?php
namespace Norm\Test\Type;

use PHPUnit_Framework_TestCase;
use Norm\Type\DateTime;

class DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testTzFormat()
    {
        $dt = new DateTime();

        $this->assertEquals(date('H'), $dt->tzFormat('H'));
        $this->assertEquals(date('H') -7, $dt->tzFormat('H', 'UTC'));
    }

    public function testLocalFormat()
    {
        $dt = new DateTime();
        $this->assertEquals(date('H'), $dt->localFormat('H'));

    }
}
