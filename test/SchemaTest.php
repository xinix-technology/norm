<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Norm\Collection;
use Norm\Connection;
use Norm\Model;
use Norm\Schema;
use Norm\Schema\NField;
use Norm\Schema\NUnknown;
use Norm\Exception\NormException;

class SchemaTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $schema = new Schema($this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']));
        $this->assertNotNull($schema->getFormatter('plain'));
        $this->assertNull($schema->getFormatter('notfound'));
    }

    public function testAddFieldByMetadata()
    {
        $collection = $this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']);
        $collection->expects($this->once())->method('resolve')->will($this->returnCallback(function($contract, $args) {
            $field = $this->getMockForAbstractClass(NField::class, [
                $args['schema'],
                $contract[1]['name'],
            ]);
            return $field;
        }));
        $schema = new Schema($collection);

        $field = $schema->addField([ NString::class, [
            'name' => 'foo',
        ]]);

        $this->assertInstanceOf(NField::class, $field);
        $this->assertInstanceOf(NUnknown::class, $schema->getField('bar'));
        $this->assertEquals($field, $schema->getField('foo'));
    }

    public function testAddFieldByInstance()
    {
        $schema = new Schema($this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']));

        $originalField = $this->getMockForAbstractClass(NField::class, [$schema, 'foo']);
        $field = $schema->addField($originalField);

        $this->assertEquals($field, $originalField);
    }

    public function testGetFilterRules()
    {
        $schema = new Schema($this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']));

        $field = $this->getMock(NField::class, null, [$schema, 'foo']);
        $field->addFilter('trim|required');
        $schema->addField($field);

        $rules = $schema->getFilterRules();
        $this->assertEquals($rules['foo']['filters'], ['trim', 'required']);
    }

    public function testFormatPlain()
    {
        $collection = $this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']);
        $schema = new Schema($collection);

        $schema->addField($this->getMock(NField::class, [], [$schema, 'foo']));

        $model = $this->getMock(Model::class, [], [ $collection ]);

        try {
            $formatted = $schema->format('plain', $model);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Cannot format explicit schema fields') {
                throw $e;
            }
        }
    }

    public function testAddAndGetFormatterAndFormat()
    {
        $collection = $this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']);
        $schema = new Schema($collection);

        $model = $this->getMock(Model::class, [], [ $collection ]);

        // function
        $formatter = function () { return 'function foo bar'; };
        $schema->addFormatter('plain', $formatter);
        $this->assertEquals($schema->getFormatter('plain'), $formatter);
        $this->assertEquals('function foo bar', $schema->format('plain', $model));

        // string variably format
        $formatter = '{foo}-{bar}';
        $schema->addFormatter('plain', $formatter);
        $this->assertEquals('-', $schema->format('plain', $model));

        // string static format
        $formatter = 'bar';
        $schema->addFormatter('plain', $formatter);
        $this->assertEquals('', $schema->format('plain', $model));

        // rejected format
        try {
            $schema->addFormatter('plain', 99);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Formatter should be callable or string format') {
                throw $e;
            }
        }

        try {
            $schema->getFormatter(88);
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Format key must be string') {
                throw $e;
            }
        }

        try {
            $schema->format('not-found', $model);
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'not found') < 0) {
                throw $e;
            }
        }
    }

    public function testFactory()
    {
        $collection = $this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']);
        // own collection
        $schema = new Schema($collection);
        $this->assertEquals($schema->factory(), $collection);

        // not found collection
        $resultCollection = $schema->factory('Bar');
        $this->assertNotEquals($resultCollection, $collection);
    }

    public function testDebugInfo()
    {

        $schema = new Schema($this->getMock(Collection::class, [], [$this->getMock(Connection::class), 'Foo']));

        $field = $this->getMock(NField::class, null, [ $schema, 'foo' ]);

        $schema->addField($field);

        $this->assertEquals($schema->__debugInfo(),  ['foo' => get_class($field)]);
    }
}