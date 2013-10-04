<?php

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MongoConnection;
use Norm\Model;
use Norm\Collection;

class ModelTest extends \PHPUnit_Framework_TestCase {

    private $connection;
    private $collection;

    public function setUp() {
        $config = array(
            'mongo' => array(
                'driver' => '\\Norm\\Connection\\MongoConnection',
                'database' => 'test',
            ),
        );
        Norm::init($config);
        $this->connection = Norm::getConnection();

        $db = $this->connection->getDB();
        $db->drop();
        $db->createCollection("user",false);

        $db->user->insert(array(
            "firstName" => "anu",
            "lastName" => "gemes",
        ));

        // $db->user->insert(array(
        //     "firstName" => "putra",
        //     "lastName" => "pramana",
        // ));

        // $db->user->insert(array(
        //     "firstName" => "farid",
        //     "lastName" => "lab",
        // ));

        // $db->user->insert(array(
        //     "firstName" => "habib",
        //     "lastName" => "chalid",
        // ));

        $this->collection = Norm::factory('User');
    }

    public function testQuery() {


        $this->assertTrue($this->collection instanceof Collection, 'is Norm::factory() returns Collection instance');

        $a = $this->collection->find();
        $this->assertTrue(is_array($a));

        $this->assertTrue($a[0] instanceof Model, 'is able to get array of Model instances');
    }

    public function testInsert() {
        $model = $this->collection->newInstance();
        $model->set('firstName', 'adoel');
        $model->set('lastName', 'razman');
        $model->save();

        $a = $this->collection->find(array(
            'firstName' => 'anu'
        ));

        $this->assertEquals(count($a), 1, 'is able to get array of Model instances');
        $this->assertEquals($a[0]->get('lastName'), 'gemes', 'is able to get array of Model instances');
    }

    public function testUpdate() {
        $model = $this->collection->findOne(array( 'firstName' => 'anu' ));

        $model->set('lastName', 'xxx');
        $model->set('age', 21);
        $model->save();

        $model = $this->collection->findOne(array(
            'firstName' => 'anu'
        ));

        $this->assertEquals($model->get('lastName'), 'xxx', 'is able to get array of Model instances');
        $this->assertEquals($model->get('age'), 21, 'is able to get array of Model instances');
    }

    public function testRemove() {
        $model = $this->collection->findOne(array( 'firstName' => 'anu' ));

        $model->remove();

        $model = $this->collection->findOne(array(
            'firstName' => 'anu'
        ));

        $this->assertNull($model, 'is null after deleted');
    }

}