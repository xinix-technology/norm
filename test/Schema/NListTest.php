<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NList;
use Norm\Type\ArrayList;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NListTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NList::class, ['name' => 'foo']);
        $prepared = $field->prepare([1,2,3]);
        $this->assertInstanceOf(ArrayList::class, $prepared);
        $this->assertEquals($prepared[1], 2);

        $prepared = $field->prepare(null);
        $this->assertEquals(count($prepared), 0);

        $this->assertEquals($field->prepare('[1,2,3]')[0], 1);
        $this->assertEquals($field->prepare(new ArrayList([1,2,3]))[0], 1);
    }

    public function testFormat()
    {
        $field = $this->injector->resolve(NList::class, ['name' => 'foo']);

        // $this->assertEquals(preg_replace('/\s+/', '', $field->format('plain', [1,2,3])), '[1,2,3]');
        $this->assertEquals($field->format('input', [1,2,3]), '__norm__/nlist/input');
        $this->assertEquals($field->format('readonly', [1,2,3]), '__norm__/nlist/readonly');
    }
}
