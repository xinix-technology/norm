<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NUnsafeText;
use Norm\Schema;

class NUnsafeTextTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NUnsafeText(null, 'foo');
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), '<b>bar</b>');
    }
}