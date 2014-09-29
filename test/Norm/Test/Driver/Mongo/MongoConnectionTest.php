<?php

namespace Norm\Test\Mongo;

use Norm\Connection\MongoConnection;
use Norm\Test\Driver\AbstractConnectionTest;

class ConnectionTest extends AbstractConnectionTest
{
    public function setUp()
    {
        $this->connection = new MongoConnection(array(
            'database' => 'test_norm'
        ));
    }

    public function testUnmarshall()
    {
        $uniqid = uniqid();
        $origin = array(
            '_id' => new \MongoId(),
            'field_string' => 'Field String',
            'field_date' => new \MongoDate(),
        );

        $result = $this->connection->unmarshall($origin);

        $message = 'Method Connection::unmarshall() expected the field [_id] ' +
            'value is copied to field [$id] string-typed.';
        $this->assertEquals($origin['_id'], $result['$id'], $message);
        $this->assertTrue(is_string($result['$id']), $message);

        $message = 'Method Connection::unmarshall() expected the field [_id] ' +
            'value is copied to field [$id] string-typed.';
        $this->assertEquals($origin['_id'], $result['$id'], $message);

        $message = 'Method Connection::unmarshall() expected the instanceof ' +
            '\MongoDate converted to \Norm\Type\DateTime.';
        $this->assertInstanceOf('\\Norm\\Type\\DateTime', $result['field_date'], $message);
    }

    public function testMarshall()
    {
        parent::testMarshall();

        // $uniqid = uniqid();
        // $origin = array(
        //     '$id' => $uniqid,
        //     'field_string' => 'Field String',
        // );
        // $result = $this->connection->marshall($origin);

        // $message = 'Method Connection::marshall() expected the field [$id] removed.';
        // $this->assertTrue(!isset($result['$id']), $message);
        // $this->assertTrue(!isset($result['id']), $message);

        // $message = 'Method Connection::marshall() expected the string-typed leave intact.';
        // $this->assertEquals('Field String', $result['field_string'], $message);
        // $this->assertTrue(is_string($result['field_string']), $message);
    }

    // public function testInsert() {
    //     $firstName = 'adoel';
    //     $lastName = 'razman';

    //     $collection = Norm::factory('User');

    //     $model = $collection->newInstance();
    //     $model->set('firstName', $firstName);
    //     $model->set('lastName', $lastName);
    //     $result = $model->save();

    //     $this->assertNotEmpty($result, 'is return not empty');

    //     $model = $collection->findOne(array(
    //         '$id' => $model->getId(),
    //     ));

    //     $this->assertEquals($model->get('firstName'), $firstName, 'has valid firstName field.');
    //     $this->assertEquals($model->get('lastName'), $lastName, 'has valid lastName field.');
    // }

    // public function testUpdate() {
    //     $lastName = 'putri';

    //     $collection = Norm::factory('User');

    //     $model = $collection->findOne(array( 'firstName' => 'putra' ));

    //     $model->set('lastName', $lastName);
    //     $result = $model->save();

    //     $this->assertNotEmpty($result, 'is return not empty');

    //     $model = $collection->findOne(array(
    //         '$id' => $model->getId()
    //     ));

    //     $this->assertEquals($model->get('lastName'), $lastName, 'has valid lastName field.');
    // }

    // public function testRemove() {

    //     $collection = Norm::factory('User');

    //     $model = $collection->findOne(array( 'firstName' => 'putra' ));
    //     $id = $model->getId();
    //     $model->remove();

    //     $this->assertNull($model->getId(), 'will lost model id after remove.');

    //     $model = $collection->findOne(array(
    //         '$id' => $id
    //     ));

    //     $this->assertNull($model, 'is null after deleted');
    // }

    // public function testQuery() {
    //     $collection = Norm::factory('User');

    //     $a = $collection->find();

    //     $this->assertTrue($a instanceof \Norm\Cursor);
    //     $this->assertEquals(count($a), 1);
    //     foreach ($a as $row) {
    //         $this->assertTrue($row instanceof Model, 'is able to get array of Model instances');
    //     }
    // }

//////////////

    // public function testSort()
    // {
    //     $collection = Norm::factory('User');

    //     $a = $collection->find()->sort(array('firstName' => 1));
    //     $a = $a->getNext();
    //     $this->assertEquals($a['firstName'], 'farid');

    //     $a = $collection->find()->sort(array('firstName' => -1));
    //     $a = $a->getNext();
    //     $this->assertEquals($a['firstName'], 'putra');
    // }

    // public function testLimit()
    // {
    //     $collection = Norm::factory('User');

    //     $a = $collection->find()->limit(2);
    //     $this->assertEquals($a->count(), 2);
    // }

    // public function testSkip()
    // {
    //     $collection = Norm::factory('User');

    //     $a = $collection->find()->skip(1);
    //     $this->assertEquals($a->getNext()->get('firstName'), 'farid');
    // }

    // public function testExpressionNE()
    // {
    //     $collection = Norm::factory('User');

    //     $a = $collection->find(array(
    //         'firstName!ne' => 'farid',
    //         ));

    //     foreach ($a as $row) {
    //         $this->assertNotEquals($row->get('firstName'), 'farid');
    //     }
    // }

    // public function testComplexCriteria()
    // {

    // }
}
