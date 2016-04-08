<?php
namespace Norm\Test\Adapter;

use DateTime;
use MongoId;
use MongoClient;
use MongoConnectionException;
use Norm\Cursor;
use Norm\Repository;
use Norm\Adapter\Mongo;
use PHPUnit_Framework_TestCase;

class MongoTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    public function setUp()
    {
        if (!class_exists('\\MongoClient')) {
            $this->markTestSkipped('Mongo client not found.');
        }
        try {
            new MongoClient('mongodb://'.MongoClient::DEFAULT_HOST.':'.MongoClient::DEFAULT_PORT);
        } catch (MongoConnectionException $e) {
            $this->markTestSkipped('Mongo server is not available.');
        }

        $this->repository = new Repository([
            'connections' => [
                'mongo' => [
                    'class' => Mongo::class,
                    'config' => [
                        'database' => 'norm_mongo_test'
                    ]
                ]
            ]
        ]);

        $this->repository->getConnection('mongo')->getRaw()->foo->remove();

        $model = $this->repository->factory('Foo')->newInstance();
        $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
        $model->save();
        $model = $this->repository->factory('Foo')->newInstance();
        $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
        $model->save();
    }

    public function testSearch()
    {
        $cursor = $this->repository->factory('Foo')->find();

        $this->assertInstanceOf(Cursor::class, $cursor);
    }

    public function testCreate()
    {
        $model = $this->repository->factory('Foo')->newInstance();
        $model->set([
            'fname' => 'John',
            'lname' => 'Doe',
        ]);
        $model->save();

        $expected = $this->repository->getConnection('mongo')->getRaw()->foo
            ->find(['_id' => new MongoId($model['$id'])])->getNext();

        $this->assertEquals(
            $expected['_id']->__toString(),
            $model['$id']
        );
    }

    public function testRead()
    {
        $this->testCreate();

        $model = $this->repository->factory('Foo')->findOne(['fname' => 'John']);
        $this->assertEquals('Doe', $model['lname']);

        $count = $this->repository->getConnection('mongo')->getRaw()->foo
            ->find()->count();
        $this->assertEquals(3, $count);
    }

    public function testUpdate()
    {
        $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model['fname'] = 'Rob';
        $model->save();

        $expected = $this->repository->getConnection('mongo')->getRaw()->foo
            ->find(['_id' => new MongoId($model['$id'])])->getNext();

        $this->assertEquals('Rob', $expected['fname']);
    }

    public function testDelete()
    {
        $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model->remove();

        $count = $this->repository->getConnection('mongo')->getRaw()->foo
            ->find()->count();

        $this->assertEquals(1, $count);
    }
}
