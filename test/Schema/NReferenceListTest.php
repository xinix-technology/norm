<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NReferenceList;
use Norm\Schema;

class NReferenceListTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NReferenceList(null, 'foo', null, 'Foo');
        $this->assertEquals(count($field->prepare(null)), 0);
        $this->assertEquals(count($field->prepare('[1,2,3]')), 3);
        $this->assertEquals(count($field->prepare('[1,2,3]')), 3);
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($t) { return $t; }));
        $field = new NReferenceList($schema, 'foo', null, [
            1 => 'Foo',
            2 => 'Bar',
        ]);

        $this->assertEquals($field->format('plain', [1,2]), 'Foo, Bar');
        $this->assertEquals($field->format('input', [1,2]), '__norm__/nreferencelist/input');
        $this->assertEquals($field->format('readonly', [1,2]), '__norm__/nreferencelist/readonly');
        $this->assertEquals($field->format('json', [1,2]), [1,2]);
    }
}