<?php
namespace Norm\Test\Observer;

use Norm\Observer\Historable;
use Norm\Test\ObserverTestCase;

class HistorableTest extends ObserverTestCase
{
    public function testSave()
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
    }
}
