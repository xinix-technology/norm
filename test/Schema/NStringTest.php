<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NString;
use Norm\Schema;

class NStringTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NString(null, 'foo');
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), 'bar');
    }
}