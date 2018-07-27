<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NUnsafeText;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NUnsafeTextTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NUnsafeText::class, ['name' => 'foo']);
        $this->assertEquals($field->prepare('foo'), 'foo');
        $this->assertEquals($field->prepare('<b>bar</b>'), '<b>bar</b>');
    }
}
