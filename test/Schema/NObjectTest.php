<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NObject;
use Norm\Schema;

class NObjectTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NObject(null, 'foo');
        $this->assertEquals($field->prepare('{"foo":"bar"}')['foo'], 'bar');
        $this->assertEquals($field->prepare(''), null);
        $obj = new \Norm\Type\Object();
        $this->assertEquals($field->prepare($obj), $obj);
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($t) {
            return $t;
        }));
        $field = new NObject($schema, 'foo');
        $this->assertEquals($field->format('input', ['foo' => 'bar']), '__norm__/nobject/input');
        $this->assertEquals($field->format('readonly', ['foo' => 'bar']), '__norm__/nobject/readonly');
    }
}