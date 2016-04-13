<?php
namespace Norm\Test\Adapter;

use DateTime;
use Norm\Cursor;
use Norm\Repository;
use Norm\Adapter\Memory;
use PHPUnit_Framework_TestCase;

class MemoryTest extends PHPUnit_Framework_TestCase
{
    protected $repository;

    public function setUp()
    {
        $this->repository = new Repository([
            'connections' => [
                [ Memory::class, [
                    'id' => 'memory',
                ]],
            ]
        ]);

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

        $this->assertEquals(
            $this->repository->getConnection('memory')->getContext()['foo'][$model['$id']]['id'],
            $model['$id']
        );
    }

    public function testRead()
    {
        $this->testCreate();

        $model = $this->repository->factory('Foo')->findOne(['fname' => 'John']);
        $this->assertEquals('Doe', $model['lname']);

        $this->assertEquals(3, count($this->repository->getConnection('memory')->getContext()['foo']));
    }

    public function testUpdate()
    {
        $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model['fname'] = 'Rob';
        $model->save();

        $this->assertEquals('Rob', $this->repository->getConnection('memory')->getContext()['foo'][$model['$id']]['fname']);
    }

    public function testDelete()
    {
        $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model->remove();

        $this->assertEquals(1, count($this->repository->getConnection('memory')->getContext()['foo']));
    }
}
