<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NDateTime;
use DateTime;
use DateTimeZone;
use Norm\Type\DateTime as TypeDateTime;
use ROH\Util\Injector;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;

class NDateTimeTest extends AbstractTest
{
    public function testPrepare()
    {
        $this->markTestSkipped('Skipped');
        $field = $this->injector->resolve(NDateTime::class, ['name' => 'foo']);

        $dt = $field->prepare('1982-11-21T01:23');
        $this->assertInstanceOf(TypeDateTime::class, $dt);
        $this->assertEquals($dt->format('H'), '01');
        $this->assertEquals($dt->serverFormat('H'), '18');

        $dt = $field->prepare('');
        $this->assertNull($dt);

        $dt = $field->prepare(new TypeDateTime('now', 'Asia/Jakarta'));
        $this->assertEquals($dt->format('H'), (date('H') + 7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        $dt = $field->prepare(time());
        $this->assertEquals($dt->format('H'), (date('H') + 7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        $dt = $field->prepare(new DateTime());
        $this->assertEquals($dt->format('H'), (date('H') + 7) % 24);
        $this->assertEquals($dt->serverFormat('H'), date('H'));

        // $field = new NDateTime('foo');
        // $dt = $field->prepare(new DateTime());
        // $this->assertEquals($dt->format('H'), date('H'));
        // $this->assertEquals($dt->serverFormat('H'), date('H'));
    }

    public function testFormat()
    {
        $this->markTestSkipped('Skipped');
        $field = $this->injector->resolve(NDateTime::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', new DateTime()), '__norm__/ndatetime/input');
        $this->assertEquals($field->format('readonly', new DateTime()), '__norm__/ndatetime/readonly');
    }
}
