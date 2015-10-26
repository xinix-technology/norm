<?php
namespace Norm\Test\Type;

use PHPUnit_Framework_TestCase;
use Norm\Type\Object;

class ObjectTest extends PHPUnit_Framework_TestCase
{
    public function testHas()
    {
        $o = new Object([
            'foo' => 'bar'
        ]);

        $this->assertTrue($o->has('bar'));
    }
}
