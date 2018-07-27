<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NFloat;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NFloatTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NFloat::class, ['name' => 'foo']);
        $this->assertTrue(is_float($field->prepare('10.5')));
        $this->assertTrue(is_float($field->prepare(10.5)));
    }
}
