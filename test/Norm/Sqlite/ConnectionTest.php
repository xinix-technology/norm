<?php

namespace Norm\Sqlite;

use Norm\Norm;
use Norm\Collection;
use Norm\Model;

require_once('Fixture.php');

class ConnectionTest extends \PHPUnit_Framework_TestCase {
    private $connection;

    public function setUp() {
        $this->connection = Fixture::init();
    }

    public function testInsert() {
        $firstName = 'adoel';
        $lastName = 'razman';

        $collection = Norm::factory('User');

        $model = $collection->newInstance();
        $model->set('firstName', $firstName);
        $model->set('lastName', $lastName);
        $result = $model->save();

        $this->assertNotEmpty($result, 'is return not empty');

        $model = $collection->findOne(array(
            '$id' => $model->getId(),
        ));
        $this->assertEquals($model->get('firstName'), $firstName, 'has valid firstName field.');
        $this->assertEquals($model->get('lastName'), $lastName, 'has valid lastName field.');
    }

    public function testUpdate() {
        $lastName = 'putri';

        $collection = Norm::factory('User');

        $model = $collection->findOne(array( 'firstName' => 'putra' ));

        $model->set('lastName', $lastName);
        $result = $model->save();

        $this->assertNotEmpty($result, 'is return not empty');

        $model = $collection->findOne(array(
            '$id' => $model->getId()
        ));

        $this->assertEquals($model->get('lastName'), $lastName, 'has valid lastName field.');
    }

    public function testRemove() {

        $collection = Norm::factory('User');

        $model = $collection->findOne(array( 'firstName' => 'putra' ));
        $id = $model->getId();
        $model->remove();

        $this->assertNull($model->getId(), 'will lost model id after remove.');

        $model = $collection->findOne(array(
            '$id' => $id
        ));

        $this->assertNull($model, 'is null after deleted');
    }

    public function testQuery() {
        $collection = Norm::factory('User');

        $a = $collection->find();

        $this->assertTrue(is_array($a));
        $this->assertEquals(count($a), 1);
        $this->assertTrue($a[0] instanceof Model, 'is able to get array of Model instances');
    }

    public function tearDown() {
        $config = Fixture::config('norm.databases');
        exec('rm -fr '.$config['sqlite']['database']);

    }
}