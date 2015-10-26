<?php
namespace Norm\Test\Observer;

use Norm\Observer\Timestampable;
use Norm\Schema\DateTime as SchemaDateTime;

class TimestampableTest extends AbstractObserverTest
{
    public function testInitialize()
    {
        $collection = $this->getCollection(new Timestampable());

        $this->assertInstanceOf(SchemaDateTime::class, $collection->getSchema()['$created_time']);
        $this->assertInstanceOf(SchemaDateTime::class, $collection->getSchema()['$updated_time']);

        $collection = $this->getCollection(new Timestampable([
            'createdKey' => 'createdAt',
            'updatedKey' => 'modifiedAt',
        ]));

        $this->assertInstanceOf(SchemaDateTime::class, $collection->getSchema()['createdAt']);
        $this->assertInstanceOf(SchemaDateTime::class, $collection->getSchema()['modifiedAt']);
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
