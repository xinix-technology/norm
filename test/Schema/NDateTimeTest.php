<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Schema\NDateTime;
use Norm\Schema;
use DateTime;
use DateTimeZone;
use Norm\Type\DateTime as TypeDateTime;

class NDateTimeTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('getAttribute')->will($this->returnValue('Asia/Jakarta'));
        $field = new NDateTime($schema, 'foo');

        $dt = $field->prepare('1982-11-21T01:23');
        $this->assertInstanceOf(TypeDateTime::class, $dt);
        $this->assertEquals($dt->format('H'), '01');
        $this->assertEquals($dt->serverFormat('H'), '18');

        $dt = $field->prepare('');
        $this->assertNull($dt);

        $dt = $field->prepare(new TypeDateTime('now', 'Asia/Jakarta'));
        $this->assertEquals($dt->format('H'), (date('H')+7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        $dt = $field->prepare(time());
        $this->assertEquals($dt->format('H'), (date('H')+7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        $dt = $field->prepare(new DateTime());
        $this->assertEquals($dt->format('H'), (date('H')+7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        $field = new NDateTime(null, 'foo');

        $dt = $field->prepare(new DateTime());
        $this->assertEquals($dt->format('H'), date('H'));
        $this->assertEquals($dt->serverFormat('H'), date('H'));
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($template) {
            return $template;
        }));
        $field = new NDateTime($schema, 'foo');

        $this->assertEquals($field->format('input', new DateTime()), '__norm__/ndatetime/input');
        $this->assertEquals($field->format('readonly', new DateTime()), '__norm__/ndatetime/readonly');
    }
}