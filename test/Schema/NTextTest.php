<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NText;
use Norm\Schema;

class NTextTest extends PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($template, $context) {
            return $context['value'];
        }));
        $field = new NText($schema, 'foo');

        $this->assertEquals($field->format('input', "foo\nbar"), "foo\nbar");
        $this->assertEquals($field->format('readonly', "foo\nbar"), "foo&lt;br /&gt;\nbar");
    }
}