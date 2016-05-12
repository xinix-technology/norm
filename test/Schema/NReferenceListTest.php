<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NReferenceList;
use Norm\Repository;
use Norm\Collection;
use Norm\Cursor;
use Norm\Connection;
use ROH\Util\Injector;
class NReferenceListTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->injector = new Injector();
        $repository = $this->getMock(Repository::class, []);
        $repository->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $this->injector->singleton(Repository::class, $repository);
        $this->injector->delegate(Connection::class, function() {
            return $this->getMockForAbstractClass(Connection::class, [$this->injector->resolve(Repository::class)]);
        });
        $this->injector->delegate(Collection::class, function() {
            return $this->getMock(Collection::class, null, [ $this->injector->resolve(Connection::class), 'Foo' ]);
        });
    }

    public function testPrepare()
    {
        $field = $this->injector->resolve(NReferenceList::class, [
            'name' => 'foo',
            'to' => 'Foo',
        ]);
        $this->assertEquals(count($field->prepare(null)), 0);
        $this->assertEquals(count($field->prepare('[1,2,3]')), 3);
        $this->assertEquals(count($field->prepare('[1,2,3]')), 3);
    }

    public function testFormat()
    {
        // $schema = $this->getMock(Schema::class, ['render'], [$this->injector->resolve(Collection::class, ['name' => 'Foo'])]);
        // $schema->method('render')->will($this->returnCallback(function($t) { return $t; }));
        $field = $this->injector->resolve(NReferenceList::class, [
            'name' => 'foo',
            'to' => [
                1 => 'Foo',
                2 => 'Bar',
            ]
        ]);

        $this->assertEquals($field->format('plain', [1,2]), 'Foo, Bar');
        $this->assertEquals($field->format('input', [1,2]), '__norm__/nreferencelist/input');
        $this->assertEquals($field->format('readonly', [1,2]), '__norm__/nreferencelist/readonly');
        $this->assertEquals($field->format('json', [1,2]), [1,2]);
    }
}