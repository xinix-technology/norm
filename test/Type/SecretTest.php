<?php
namespace Norm\Test\Type;

use PHPUnit\Framework\TestCase;
use Norm\Type\Secret;

class SecretTest extends TestCase
{
    public function testHas()
    {
        $s = new Secret('wow');

        $this->assertEquals('', $s->jsonSerialize());
    }

    public function testToString()
    {
        $s = new Secret('wow');

        $this->assertEquals($s, $s->__toString());
    }
}
