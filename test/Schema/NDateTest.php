<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NDate;
use Norm\Schema;
use DateTime;

class NDateTest extends PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $field = new NDate($schema, 'foo');

        $this->assertEquals($field->format('input', new DateTime()), '__norm__/ndate/input');
        $this->assertEquals($field->format('readonly', new DateTime()), '__norm__/ndate/readonly');

        $this->assertEquals($field->format('plain', new DateTime()), date('Y-m-d'));
        $this->assertEquals($field->format('plain', null), '');
    }
}