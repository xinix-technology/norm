<?php
namespace Norm\Test\Schema;

use PHPUnit\Framework\TestCase;
use Norm\Schema\NPassword;
use Norm\Type\Secret;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use ROH\Util\Injector;

class NPasswordTest extends AbstractTest
{
    public function testPrepare()
    {
        $field = $this->injector->resolve(NPassword::class, ['name' => 'foo']);
        $this->assertInstanceOf(Secret::class, $field->prepare('foo'));
        $this->assertEquals($field->prepare(''), null);
        $secret = new Secret('');
        $this->assertEquals($field->prepare($secret), $secret);
    }

    public function testFormat()
    {
        $field = $this->injector->resolve(NPassword::class, ['name' => 'foo']);
        $this->assertEquals($field->format('json', 'foo'), null);
        $this->assertEquals($field->format('plain', 'foo'), '');

        $field = $this->injector->resolve(NPassword::class, ['name' => 'foo']);
        $this->assertEquals($field->format('input', 'foo'), '__norm__/npassword/input');
        $this->assertEquals($field->format('readonly', 'foo'), '__norm__/npassword/readonly');
    }
}
