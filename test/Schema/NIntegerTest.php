<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NInteger;
use Norm\Schema;

class NIntegerTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NInteger(null, 'foo');
        $this->assertTrue(is_int($field->prepare('10.5')));
        $this->assertTrue(is_int($field->prepare(10.5)));
    }
}