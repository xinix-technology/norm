<?php

namespace Norm\Mysql;

use Norm\Norm;
use Norm\Connection;
use Norm\Connection\MysqlConnection;
use Norm\Model;
use Norm\Collection;

require_once('Fixture.php');

class ModelTest extends \PHPUnit_Framework_TestCase {

    private static $connection;
    private static $collection;
    private static $db;
    private static $model;

    public static function setUpBeforeClass() {
        if (self::$collection) {
            return;
        }

        Norm::init(Fixture::config('norm.databases'));

        self::$connection = Norm::getConnection();

        self::$db = self::$connection->getDB();

        self::$collection = Norm::factory('Users');

        $collectionName = self::$collection->name;

        $drop = self::$db->exec("DELETE FROM $collectionName");

        self::$model = self::$collection->newInstance();
    }

    public function testQuery() {
        $this->assertTrue(self::$collection instanceof Collection, 'is Norm::factory() returns Collection instance');
        $a = self::$connection->listCollections();
        $this->assertTrue(is_array($a));
    }

    public function testInsert() {
        self::$model->set('name', 'adoel');
        self::$model->set('hobby', 'hiking');
        self::$model->set('age', '22');

        $this->assertEquals(count(self::$model->save()), 1, 'is able to save array of Model instances to database');
    }

    public function testUpdate() {
        $this->assertEquals(self::$model->get('hobby'), 'hiking', 'index of attributes has not changed yet');

        self::$model->set('hobby', 'jogging');
        self::$model->save();

        $this->assertEquals(self::$model->get('hobby'), 'jogging', 'index of attributes has changed');
    }

    public function testRemove() {
        $model = self::$collection->findOne(array('name' => 'adoel'));

        $this->assertNotNull($model, 'is not null before deleted');

        $model->remove();

        $model = self::$collection->findOne(array(
            'name' => 'adoel'
        ));

        $this->assertNull($model, 'is null after deleted');
    }

}
