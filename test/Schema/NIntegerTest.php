<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NInteger;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NIntegerTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NInteger::class, ['name' => 'foo']);
        $this->assertTrue(is_int($field->prepare('10.5')));
        $this->assertTrue(is_int($field->prepare(10.5)));
    }
}
