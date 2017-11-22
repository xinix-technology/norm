<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NFloat;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NFloatTest extends TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
        $repository = $this->getMock(Repository::class, []);
        $repository->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $this->injector->singleton(Repository::class, $repository);
        $this->injector->singleton(Connection::class, $this->getMockForAbstractClass(Connection::class, [$repository]));
        $this->injector->singleton(Collection::class, $this->getMock(Collection::class, null, [ $this->injector->resolve(Connection::class), 'Foo' ]));
    }

    public function testPrepare()
    {
        $field = $this->injector->resolve(NFloat::class, ['name' => 'foo']);
        $this->assertTrue(is_float($field->prepare('10.5')));
        $this->assertTrue(is_float($field->prepare(10.5)));
    }
}