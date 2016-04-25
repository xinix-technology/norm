<?php
namespace Norm\Test\Observer;

use Norm\Observer\Historable;

class HistorableTest extends AbstractObserverTest
{
    public function testSaveAndRemove()
    {
        $collection = $this->getCollection(new Historable());

        $model = $collection->newInstance();
        $model['name'] = '1';
        $model->save();

        $this->assertEquals('new', $collection->factory('FooHistory')->findOne([
            'model_id' => $model['$id']
        ])['type']);

        $model['name'] = '2';
        $model->save();
        $this->assertEquals(2, count($collection->factory('FooHistory')->find([
            'model_id' => $model['$id']
        ])));

        $model = $collection->findOne();

        $this->assertEquals(2, count($model['$history']));
        $this->assertTrue(is_array($model['$history']));

        $model->remove();

        $this->assertEquals(3, count($model['$history']));

    }
}
