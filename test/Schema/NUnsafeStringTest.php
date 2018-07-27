<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NUnsafeString;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NUnsafeStringTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NUnsafeString::class, ['name' => 'foo']);
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), '<b>bar</b>');
    }
}
