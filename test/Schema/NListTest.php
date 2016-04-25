<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NList;
use Norm\Schema;
use Norm\Type\ArrayList;

class NListTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NList(null, 'foo');
        $prepared = $field->prepare([1,2,3]);
        $this->assertInstanceOf(ArrayList::class, $prepared);
        $this->assertEquals($prepared[1], 2);

        $prepared = $field->prepare(null);
        $this->assertEquals(count($prepared), 0);

        $this->assertEquals($field->prepare('[1,2,3]')[0], 1);
        $this->assertEquals($field->prepare(new ArrayList([1,2,3]))[0], 1);
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($t) {
            return $t;
        }));
        $field = new NList($schema, 'foo');
        $this->assertEquals(preg_replace('/\s+/', '', $field->format('plain', [1,2,3])), '[1,2,3]');
        $this->assertEquals($field->format('input', [1,2,3]), '__norm__/nlist/input');
        $this->assertEquals($field->format('readonly', [1,2,3]), '__norm__/nlist/readonly');
    }
}