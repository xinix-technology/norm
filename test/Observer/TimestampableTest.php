<?php
namespace Norm\Test\Observer;

use Norm\Observer\Timestampable;
use Norm\Schema\NDateTime;

class TimestampableTest extends AbstractObserverTest
{
    public function testInitialize()
    {
        $collection = $this->getCollection(new Timestampable());

        $this->assertInstanceOf(NDateTime::class, $collection->getField('$created_time'));
        $this->assertInstanceOf(NDateTime::class, $collection->getField('$updated_time'));

        $collection = $this->getCollection(new Timestampable([
            'createdKey' => 'createdAt',
            'updatedKey' => 'modifiedAt',
        ]));

        $this->assertInstanceOf(NDateTime::class, $collection->getField('createdAt'));
        $this->assertInstanceOf(NDateTime::class, $collection->getField('modifiedAt'));
    }

    public function testSave()
    {
        $collection = $this->getCollection(new Timestampable());

        $model = $collection->newInstance();
        $model['foo'] = 'bar';
        $model->save();

        $this->assertEquals($model['$created_time'], $model['$updated_time']);

        $model['$created_time'] = time() - 1;
        $model->save();

        $this->assertNotEquals($model['$created_time'], $model['$updated_time']);
    }
}
