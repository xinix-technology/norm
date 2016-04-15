<?php
namespace Norm\Test;

use PHPUnit_Framework_TestCase;
use Closure;
use Norm\Schema;
use Norm\Repository;
use Norm\Collection;
use Norm\Connection;
use Norm\Schema\NString;
use Norm\Schema\NUnknown;
use Norm\Exception\NormException;

class SchemaTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    protected $collection;

    protected $schema;

    public function setUp()
    {
        parent::setUp();

        $connection = $this->getMock(Connection::class);

        $this->repository = new Repository();
        $this->repository->singleton(Connection::class, $connection);

        $this->collection = $this->repository->resolve(Collection::class, [
            'options' => [
                'name' => 'Test'
            ]
        ]);

        $this->repository->singleton(Collection::class, $this->collection);

        $this->schema = $this->repository->resolve(Schema::class);
    }

    public function testAddFieldByMetadata()
    {

        $field = $this->schema->addField([ NString::class, [
            'options' => [
                'name' => 'foo',
            ],
        ]]);

        $this->assertInstanceOf(NString::class, $field);
        $this->assertInstanceOf(NUnknown::class, $this->schema['bar']);
        $this->assertEquals($field, $this->schema['foo']);
    }

    public function testAddFieldByInstance()
    {
        $originalField = new NString($this->repository, $this->schema, ['name' => 'foo']);
        $field = $this->schema->addField($originalField);

        $this->assertEquals($field, $originalField);
    }

    public function testGetFilterRules()
    {
        $this->schema->addField([ NString::class, [
            'options' => [
                'name' => 'foo',
                'filter' => 'trim|required'
            ]
        ]]);

        $rules = $this->schema->getFilterRules();
        $this->assertEquals($rules['foo']['filters'], ['trim', 'required']);
    }

    public function testFormatPlain()
    {
        $this->schema->addField([ NString::class, [
            'options' => [
                'name' => 'foo',
            ]
        ]]);

        $model = $this->collection->newInstance([
            'foo' => 'Foo Bar',
        ]);
        $formatted = $this->schema->format('plain', $model);
        $this->assertEquals($formatted, 'Foo Bar');
    }

    public function testAddAndGetFormatterAndFormat()
    {
        $model = $this->collection->newInstance([
            'foo' => 'Foo',
            'bar' => 'Bar',
        ]);

        // function
        $formatter = function () { return 'function foo bar'; };
        $this->schema->addFormatter('plain', $formatter);
        $this->assertEquals($this->schema->getFormatter('plain'), $formatter);
        $this->assertEquals('function foo bar', $this->schema->format('plain', $model));

        // string variably format
        $formatter = '{foo}-{bar}';
        $this->schema->addFormatter('plain', $formatter);
        $this->assertInstanceof(Closure::class, $this->schema->getFormatter('plain'));
        $this->assertEquals('Foo-Bar', $this->schema->format('plain', $model));

        // string static format
        $formatter = 'bar';
        $this->schema->addFormatter('plain', $formatter);
        $this->assertInstanceof(Closure::class, $this->schema->getFormatter('plain'));
        $this->assertEquals('Bar', $this->schema->format('plain', $model));

        // rejected format
        try {
            $this->schema->addFormatter('plain', 99);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Formatter should be callable or string format') {
                throw $e;
            }
        }

        try {
            $this->schema->getFormatter(88);
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Format key must be string') {
                throw $e;
            }
        }

        try {
            $this->schema->format('not-found', $model);
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'not found') < 0) {
                throw $e;
            }
        }
    }

    public function testFactory()
    {
        // own collection
        $this->assertEquals($this->schema->factory(), $this->collection);

        // not found collection
        try {
            $this->schema->factory('Bar');
            $this->fail('Must not here');
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'No connection available to create collection') < 0) {
                throw $e;
            }
        }
    }
}