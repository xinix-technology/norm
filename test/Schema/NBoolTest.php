<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NBool;
use ROH\Util\Injector;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;

class NBoolTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NBool::class, ['name' => 'foo']);

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
        $field = $this->injector->resolve(NBool::class, ['name' => 'foo']);
        $result = $field->format('input');
        $this->assertEquals($result, '__norm__/nbool/input');
    }

    public function testFormatPlain()
    {
        $field = $this->injector->resolve(NBool::class, ['name' => 'foo']);
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
