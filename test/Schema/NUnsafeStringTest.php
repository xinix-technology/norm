<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NUnsafeString;
use Norm\Schema;

class NUnsafeStringTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NUnsafeString(null, 'foo');
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), '<b>bar</b>');
    }
}