<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NPassword;
use Norm\Schema;
use Norm\Type\Secret;

class NPasswordTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $field = new NPassword(null, 'foo');
        $this->assertInstanceOf(Secret::class, $field->prepare('foo'));
        $this->assertEquals($field->prepare(''), null);
        $secret = new Secret('');
        $this->assertEquals($field->prepare($secret), $secret);
    }

    public function testFormat()
    {
        $field = new NPassword(null, 'foo');
        $this->assertEquals($field->format('json', 'foo'), null);
        $this->assertEquals($field->format('plain', 'foo'), '');

        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($t) {
            return $t;
        }));
        $field = new NPassword($schema, 'foo');
        $this->assertEquals($field->format('input', 'foo'), '__norm__/npassword/input');
        $this->assertEquals($field->format('readonly', 'foo'), '__norm__/npassword/readonly');
    }
}