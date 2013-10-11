<?php

namespace Norm\Mysql;

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MysqlConnection;
use Norm\Model;
use Norm\Collection;

require_once('Fixture.php');

class MultiModellingTest extends \PHPUnit_Framework_TestCase {

    private $connection;
    private $collection;
    private $db;
    private $model;

    public function setUp() {

        Norm::init(Fixture::config('norm.databases'));

        $this->connection = Norm::getConnection();

        $this->db = $this->connection->getDB();

        $this->collection = Norm::factory('Users');

        $collectionName = $this->collection->name;

        $this->model = $this->collection->newInstance();

        // 2nd model
        $this->model2 = $this->collection->newInstance();

        $drop = $this->db->exec("DELETE FROM $collectionName");
    }

    public function testQuery() {
        $this->assertTrue($this->collection instanceof Collection, 'is Norm::factory() returns Collection instance');
        $a = $this->connection->listCollections();
        $this->assertTrue(is_array($a));
    }

    public function testInsert() {
        $this->model->set('name', 'adoel');
        $this->model->set('hobby', 'hiking');
        $this->model->set('age', '22');

        $this->assertEquals(count($this->model->save()), 1, 'is able to insert Model attributes to database');

        // 2nd Insert
        $this->model2->set('name', 'alfa');
        $this->model2->set('hobby', 'gaming');
        $this->model2->set('age', '21');

        $this->assertEquals(count($this->model2->save()), 1, 'is able to insert Model attributes to database');

        $a = $this->collection->findOne()->dump();
        $this->assertEquals(count($a), 2, 'is able to get all Model attributes from database');
    }

    public function testUpdate() {
        $this->model->set('name', 'adoel');
        $this->model->set('hobby', 'hiking');
        $this->model->set('age', '22');

        $this->model->save();

        $this->model2->set('name', 'alfa');
        $this->model2->set('hobby', 'gaming');
        $this->model2->set('age', '22');

        $this->model2->save();

        $this->assertEquals($this->model->get('hobby'), 'hiking', 'is able to update Model attributes to database');
        $this->assertEquals($this->model2->get('hobby'), 'gaming', 'is able to update Model attributes to database');

        $this->model->set('hobby', 'jogging');
        $this->model->save();

        $this->model2->set('hobby', 'coding');
        $this->model2->save();

        $this->assertEquals($this->model->get('hobby'), 'jogging', 'is able to update Model attributes to database');
        $this->assertEquals($this->model2->get('hobby'), 'coding', 'is able to update Model attributes to database');
    }

    public function testRemove() {
        $this->model->set('name', 'adoel');
        $this->model->set('hobby', 'hiking');
        $this->model->set('age', '22');

        $this->model->save();

        // Interupter
        $this->model2->set('name', 'alfa');
        $this->model2->set('hobby', 'joking');
        $this->model2->set('age', '22');

        $this->model2->save();

        $model = $this->collection->findOne(array('name' => 'adoel'));
        $model2 = $this->collection->findOne(array('name' => 'adoel'));

        $this->assertNotNull($model, 'is not null before deleted');

        $model->remove();

        $model = $this->collection->findOne(array(
            'name' => 'adoel'
        ));

        $this->assertNull($model, 'is null after deleted');
        $this->assertNotNull($model2, 'is not null because is not deleted');
    }

}
