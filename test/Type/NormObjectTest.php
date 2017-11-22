<?php
namespace Norm\Test\Type;

use PHPUnit\Framework\TestCase;
use Norm\Type\NormObject;

class NormObjectTest extends TestCase
{
    public function testHas()
    {
        $o = new NormObject([
            'foo' => 'bar'
        ]);

        $this->assertTrue($o->has('bar'));
    }
}
