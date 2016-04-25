<?php
namespace Norm\Test\Schema;

use PHPUnit_Framework_TestCase;
use Norm\Repository;
use Norm\Normable;
use Norm\Collection;
use Norm\Schema\NField;
use Norm\Schema;
use Norm\Exception\NormException;

class NFieldTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        try {
            $this->getMockForAbstractClass(NField::class);
            $this->fail('must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Name (2nd argument) must be string or array, and must not empty') {
                throw $e;
            }
        }

        $customFilter = function() {};
        $field = $this->getMockForAbstractClass(NField::class, [null, 'foo', ['trim', $customFilter], [
            'foo' => 'bar',
        ]]);
        $this->assertEquals($field->getFilter()[0], 'trim');
        $this->assertEquals($field->getFilter()[1], $customFilter);
        $this->assertEquals($field['name'], 'foo');
        $this->assertEquals($field['label'], 'Foo');
        $this->assertTrue(isset($field['foo']));
        $this->assertEquals($field['foo'], 'bar');
        unset($field['foo']);
        $this->assertFalse(isset($field['foo']));

        try {
            $field = $this->getMockForAbstractClass(NField::class, [null, ['foo']]);
            $this->fail('must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Name (2nd argument) must be array consists of name and label') {
                throw $e;
            }
        }

        $field = $this->getMockForAbstractClass(NField::class, [null, ['foo', 'Bar']]);
        $this->assertEquals($field['name'], 'foo');
        $this->assertEquals($field['label'], 'Bar');
    }

    public function testFactory()
    {
        $field = $this->getMockForAbstractClass(NField::class, [null, 'foo']);
        try {
            $field->factory();
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Field does not have schema yet!') {
                throw $e;
            }
        }

        $schema = $this->getMock(Schema::class);
        $schema->method('factory')->will($this->returnValue('foo'));
        $field = $this->getMockForAbstractClass(NField::class, [ $schema, 'Foo' ]);
        $this->assertEquals($field->factory(), 'foo');
    }

    public function testFormat()
    {
        $schema = $this->getMock(Schema::class);
        $schema->method('render')->will($this->returnCallback(function($file) {
            return $file;
        }));
        $field = $this->getMockForAbstractClass(NField::class, [$schema, 'foo']);
        $this->assertEquals($field->format('plain'), '');
        $this->assertEquals($field->format('plain', 'foo'), 'foo');

        $this->assertEquals($field->format('input', 'foo'), '__norm__/nfield/input');
        $this->assertEquals($field->format('readonly', 'foo'), '__norm__/nfield/readonly');
        $this->assertEquals($field->format('label'), '__norm__/nfield/label');
        $this->assertEquals($field->format('json', 'foo'), 'foo');

        $this->assertEquals($field->set('readonly', true)->format('input', 'foo'), '__norm__/nfield/readonly');

        try {

            $field->format('unknown', 'foo');
            $this->fail('Must not here');
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'Formatter not found, ') !== 0) {
                throw $e;
            }
        }
    }

    public function testDebugInfo()
    {
        $field = $this->getMockForAbstractClass(NField::class, [null, 'foo']);
        $this->assertEquals($field->__debugInfo()['name'], 'foo');
        $this->assertEquals($field->__debugInfo()['label'], 'Foo');
    }
}