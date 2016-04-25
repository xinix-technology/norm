<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NFloat;
use Norm\Schema;

class NFloatTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NFloat(null, 'foo');
        $this->assertTrue(is_float($field->prepare('10.5')));
        $this->assertTrue(is_float($field->prepare(10.5)));
    }
}