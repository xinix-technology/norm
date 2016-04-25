<?php
namespace Norm\Test\Exception;

use PHPUnit_Framework_TestCase;
use Norm\Exception\FilterException;

class FilterExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testContext()
    {
        $e = new FilterException();
        $context = 'foo';
        $e->setContext($context);
        $this->assertEquals($e->getContext(), $context);
    }

    public function testChildren()
    {
        $e = new FilterException();
        $this->assertFalse($e->hasChildren());
    }
}