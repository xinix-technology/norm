<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NText;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NTextTest extends AbstractTest
{
    public function testFormat()
    {
        $field = $this->injector->resolve(NText::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', "foo\nbar"), '__norm__/ntext/input');
        $this->assertEquals($field->format('readonly', "foo\nbar"), '__norm__/nfield/readonly');
    }
}
