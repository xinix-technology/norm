<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NBool;
use Norm\Schema;

class NBoolTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NBool(null, 'foo');

        $this->assertEquals($field->prepare('1'), true);
        $this->assertEquals($field->prepare(1), true);
        $this->assertEquals($field->prepare(true), true);

        $this->assertEquals($field->prepare(null), false);
        $this->assertEquals($field->prepare(''), false);
        $this->assertEquals($field->prepare('0'), false);
        $this->assertEquals($field->prepare(0), false);
        $this->assertEquals($field->prepare(false), false);
    }

    public function testFormatInput()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $field = new NBool($schema, 'foo');
        $result = $field->format('input');
        $this->assertEquals($result, '_schema/nbool/input');
    }

    public function testFormatPlain()
    {
        $field = new NBool(null, 'foo');
        $this->assertEquals($field->format('plain', true), 'True');
        $this->assertEquals($field->format('plain', 1), 'True');
        $this->assertEquals($field->format('plain', 100), 'True');
        $this->assertEquals($field->format('plain', 'foo'), 'True');
        $this->assertEquals($field->format('plain', ['foo']), 'True');

        $this->assertEquals($field->format('plain'), 'False');
        $this->assertEquals($field->format('plain', false), 'False');
        $this->assertEquals($field->format('plain', 0), 'False');
        $this->assertEquals($field->format('plain', null), 'False');
        $this->assertEquals($field->format('plain', ''), 'False');
        $this->assertEquals($field->format('plain', []), 'False');
    }
}