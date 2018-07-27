<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NString;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NStringTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NString::class, ['name' => 'foo']);
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), 'bar');
    }
}
