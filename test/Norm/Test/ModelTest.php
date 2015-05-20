<?php

namespace Norm\Test;

use Norm\Collection;
use Norm\Model;
use Norm\Connection\MemoryConnection;
use Norm\Schema\String;

class ModelTest extends \PHPUnit_Framework_TestCase
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
        ));

        $this->collection = new Collection(array(
            'name' => 'TestCollection',
            'schema' => $this->schema,
            'connection' => $this->connection,
        ));
    }

    public function testInstance()
    {
        $model = $this->collection->findOne();

        $this->assertInstanceOf('Norm\Model', $model);
    }

    public function testGet()
    {
        $model = $this->collection->findOne();

        $this->assertEquals($model->field_a, 'a1');

        $this->assertEquals($model->get('field_a'), 'a1');

        $this->assertEquals($model['field_a'], 'a1');
    }

    public function testSet()
    {
        $model = $this->collection->findOne();

        $model->field_a = 'foo';
        $this->assertEquals($model->field_a, 'foo');

        $model->set('field_a', 'bar');
        $this->assertEquals($model->get('field_a'), 'bar');

        $model['field_a'] = 'baz';

        $this->assertEquals($model['field_a'], 'baz');
    }

    public function testJson()
    {
        $model = $this->collection->findOne();

        $firstJson = json_encode($model->jsonSerialize());
        $secondJson = (string) $model;
        $thirdJson = json_encode($model->toArray());

        $this->assertTrue(is_string($firstJson));
        $this->assertTrue(is_string($secondJson));
        $this->assertTrue(is_string($thirdJson));

        $this->assertEquals($firstJson, $secondJson);
        $this->assertEquals($firstJson, $thirdJson);
        $this->assertEquals($secondJson, $thirdJson);
    }
}
