<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NToken;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NTokenTest extends AbstractTest
{
    public function testFormat()
    {
        $field = $this->injector->resolve(NToken::class, ['name' => 'foo']);
        $this->assertEquals($field->format('input', 'foo'), '__norm__/ntoken/input');
    }
}
