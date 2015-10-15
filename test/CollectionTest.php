<?php

namespace Norm\Test;

use Norm\Collection;
use Norm\Connection\MemoryConnection;
use Norm\Schema\String;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    protected $schema;

    protected $collection;

    protected $connection;

    public function setUp()
    {
        $this->schema = array(
            'field_a' => String::create('field_a'),
            'field_b' => String::create('field_b')->filter('trim'),
        );

        $this->connection = new MemoryConnection(array(
            'name' => 'test_connection',
        ));

        $this->connection->setCollectionData('test_collection', array(
            array(
                'field_a' => 'a1',
                'field_b' => 'b1',
            ),
            array(
                'field_a' => 'a2',
                'field_b' => 'b2',
            ),
            array(
                'field_a' => 'a3',
                'field_b' => 'b3',
            ),
        ));

        $this->collection = new Collection(array(
            'name' => 'TestCollection',
            'schema' => $this->schema,
            'connection' => $this->connection,
        ));
    }

    public function testGetName()
    {
        $name = $this->collection->getName();

        $message = 'Collection::getName() expected return collection name as string';
        $this->assertEquals('test_collection', $name, $message);
        $this->assertTrue(is_string($name), $message);
    }

    public function testGetClass()
    {
        $clazz = $this->collection->getClass();

        $message = 'Collection::getClass() expected return collection class as string';
        $this->assertEquals('TestCollection', $clazz, $message);
        $this->assertTrue(is_string($clazz), $message);
    }

    public function testOption()
    {
        $option = $this->collection->option();
        $message = 'Collection::option() expected return array if no key specified.';
        $this->assertTrue(is_array($option), $message);
        $this->assertEquals('TestCollection', $option['name'], $message);
    }

    public function testSchema()
    {
        $schema = $this->collection->schema();

        $message = 'Collection::schema() expected return array of schema if no key specified';
        $this->assertNotNull(@$schema['field_a'], $message);

        $schema = $this->collection->schema('field_b');

        $message = 'Collection::schema() expected return field schema';
        $this->assertEquals($this->schema['field_b'], $schema, $message);
    }

    public function testPrepare()
    {
        $result = $this->collection->prepare('field_b', 'test');
        $this->assertEquals('test', $result, 'Collection::prepare() expected return value from schema');

        $result = $this->collection->prepare('field_c', 'test');
        $this->assertEquals('test', $result, 'Collection::prepare() expected return identity value if no schema');
    }

    public function testAttach()
    {
        $document = array(
            'field_a' => 'x',
            'field_b' => 'y',
            '_field_c' => 'z',
        );
        $result = $this->collection->attach($document);

        $message = 'Collection::attach() expected return instance of Model';
        $this->assertInstanceOf('Norm\\Model', $result, $message);

        $message = 'Collection::attach() expected call unmarshalled';
        $this->assertEquals('y', $result['field_b'], $message);
    }

    public function testFind()
    {
        $all = $this->collection->find();
        $allArray = $all->toArray();

        $message = 'Collection::find() expected return instance of Cursor';
        $this->assertInstanceOf('Norm\\Cursor', $all, $message);
        $this->assertCount(3, $allArray, $message);
    }

    public function testFindOne()
    {
        $model =  $this->collection->findOne();
        $message = 'Collection::findOne() expected return instance of Model';
        $this->assertInstanceOf('Norm\\Model', $model, $message);

        $model =  $this->collection->findOne(array('field_a' => 'a2'));
        $message = 'Collection::findOne() expected return second row';

        $this->assertEquals('b2', $model['field_b'], $message);

        $model =  $this->collection->findOne(array('field_a' => 'a10'));
        $message = 'Collection::findOne() expected return null';
        $this->assertNull($model, $message);
    }

    public function testNewInstance()
    {
        $model = $this->collection->newInstance();

        $message = 'Collection::newInstance() expected return instance of Model';
        $this->assertInstanceOf('Norm\\Model', $model, $message);

        $fieldValue = uniqid();
        $model = $this->collection->newInstance(array(
            'a_field' => $fieldValue,
        ));

        $message = 'Collection::newInstance() data expected prepopulated from argument';
        $this->assertEquals($fieldValue, $model['a_field'], $message);
    }

    public function testFilter()
    {
        $model = $this->collection->newInstance();
        $model['field_b'] = '       test      ';
        $result = $this->collection->filter($model);

        $message = 'Collection::filter() expected field_b field to be trimmed';
        $this->assertEquals('test', $model['field_b'], $message);
        $this->assertEquals('test', $result['field_b'], $message);
    }

    public function testSave()
    {
        $data = array(
            'field_a' => uniqid(),
            'field_b' => uniqid(),
        );
        $model = $this->collection->newInstance();
        $model->set($data);

        $this->collection->save($model);

        $message = 'Collection::save() expected model to have id';
        $this->assertArrayHasKey('$id', $model->toArray(), $message);

        $found = $this->collection->findOne(array('field_a' => $data['field_a']));

        $message = 'Collection::save() expected to find data after saved';
        $this->assertNotNull($found, $message);
    }

    public function testRemove()
    {
        $model = $this->collection->findOne();
        $this->collection->remove($model);

        $message = 'Collection::remove() expected to remove one document from collection';
        $this->assertEquals(2, $this->collection->find()->count(), $message);
    }

    public function testRemoveAll()
    {
        $this->collection->remove();

        $message = 'Collection::remove() expected to remove one document from collection';
        $this->assertEquals(0, $this->collection->find()->count(), $message);
    }
}
