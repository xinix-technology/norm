<?php
namespace Norm\Test\Exception;

use PHPUnit\Framework\TestCase;
use Norm\Exception\FilterException;

class FilterExceptionTest extends TestCase
{
    public function testContext()
    {
        $e = new FilterException();
        $field = 'foo';
        $e->setField($field);
        $this->assertEquals($e->getField(), $field);
    }

    public function testChildren()
    {
        $e = new FilterException();
        $this->assertFalse($e->hasChildren());
    }
}
