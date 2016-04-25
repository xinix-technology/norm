<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NToken;
use Norm\Schema;

class NTokenTest extends PHPUnit_Framework_TestCase
{
    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($t) {
            return $t;
        }));
        $field = new NToken($schema, 'foo');
        $this->assertEquals($field->format('input', 'foo'), '__norm__/ntoken/input');
    }
}