<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NDate;
use DateTime;
use ROH\Util\Injector;
use Norm\Repository;
use Norm\Connection;
use Norm\Collection;

class NDateTest extends AbstractTest
{
    public function testFormat()
    {
        $field = $this->injector->resolve(NDate::class, ['name' => 'foo']);

        $this->assertEquals($field->format('input', new DateTime()), '__norm__/ndate/input');
        $this->assertEquals($field->format('readonly', new DateTime()), '__norm__/ndate/readonly');

        $this->assertEquals($field->format('plain', new DateTime()), date('Y-m-d'));
        $this->assertEquals($field->format('plain', null), '');
    }
}
