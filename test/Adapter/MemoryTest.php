<?php
namespace Norm\Test\Adapter;

use DateTime;
use Norm\Cursor;
use Norm\Norm as TheNorm;
use Norm\Adapter\Memory;

class MemoryTest extends \PHPUnit_Framework_TestCase
{
    protected $norm;

    public function setUp()
    {
        $this->norm = new TheNorm([
            'connections' => [
                'memory' => [
                    'class' => Memory::class
                ]
            ]
        ]);

        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
        $model->save();
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
        $model->save();
    }

    public function testSearch()
    {
        $cursor = $this->norm->factory('Foo')->find();

        $this->assertInstanceOf(Cursor::class, $cursor);
    }

    public function testCreate()
    {
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set([
            'fname' => 'John',
            'lname' => 'Doe',
        ]);
        $model->save();

        $this->assertEquals(
            $this->norm->getConnection('memory')->getContext()['foo'][$model['$id']]['id'],
            $model['$id']
        );
    }

    public function testRead()
    {
        $this->testCreate();

        $model = $this->norm->factory('Foo')->findOne(['fname' => 'John']);
        $this->assertEquals('Doe', $model['lname']);

        $this->assertEquals(3, count($this->norm->getConnection('memory')->getContext()['foo']));
    }

    public function testUpdate()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model['fname'] = 'Rob';
        $model->save();

        $this->assertEquals('Rob', $this->norm->getConnection('memory')->getContext()['foo'][$model['$id']]['fname']);
    }

    public function testDelete()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model->remove();

        $this->assertEquals(1, count($this->norm->getConnection('memory')->getContext()['foo']));
    }
}
