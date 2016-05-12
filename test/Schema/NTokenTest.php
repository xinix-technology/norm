<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NToken;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NTokenTest extends PHPUnit_Framework_TestCase
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
        $field = $this->injector->resolve(NToken::class, ['name' => 'foo']);
        $this->assertEquals($field->format('input', 'foo'), '__norm__/ntoken/input');
    }
}