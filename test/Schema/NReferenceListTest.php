<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NReferenceList;
use Norm\Repository;
use Norm\Collection;
use Norm\Cursor;
use Norm\Connection;
use ROH\Util\Injector;
class NReferenceListTest extends AbstractTest
{
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
        $this->markTestSkipped('Skipped');

        // $schema = $this->getMock(Schema::class, ['render'], [$this->injector->resolve(Collection::class, ['name' => 'Foo'])]);
        // $schema->method('render')->will($this->returnCallback(function ($t) { return $t; }));
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
