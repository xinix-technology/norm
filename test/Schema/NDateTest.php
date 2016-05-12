<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NDate;
use DateTime;
use ROH\Util\Injector;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;

class NDateTest extends PHPUnit_Framework_TestCase
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
        $field = $this->injector->resolve(NDate::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', new DateTime()), '__norm__/ndate/input');
        $this->assertEquals($field->format('readonly', new DateTime()), '__norm__/ndate/readonly');

        $this->assertEquals($field->format('plain', new DateTime()), date('Y-m-d'));
        $this->assertEquals($field->format('plain', null), '');
    }
}