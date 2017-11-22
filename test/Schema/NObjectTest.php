<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NObject;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NObjectTest extends TestCase
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
        $field = $this->injector->resolve(NObject::class, ['name' => 'foo']);
        $this->assertEquals($field->prepare('{"foo":"bar"}')['foo'], 'bar');
        $this->assertEquals($field->prepare(''), null);
        $obj = new \Norm\Type\Object();
        $this->assertEquals($field->prepare($obj), $obj);
    }

    public function testFormat()
    {
        $field = $this->injector->resolve(NObject::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', ['foo' => 'bar']), '__norm__/nobject/input');
        $this->assertEquals($field->format('readonly', ['foo' => 'bar']), '__norm__/nobject/readonly');
    }
}
