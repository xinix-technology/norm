<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NText;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NTextTest extends TestCase
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

    public function testFormat()
    {
        $field = $this->injector->resolve(NText::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', "foo\nbar"), '__norm__/ntext/input');
        $this->assertEquals($field->format('readonly', "foo\nbar"), '__norm__/nfield/readonly');
    }
}