<?php
namespace Norm\Test\Observer;

use Norm\Observer\Actorable;
use Norm\Test\ObserverTestCase;
use Norm\Collection;

class ActorableTest extends ObserverTestCase
{
    public function testSave()
    {
        $collection = $this->getCollection(new Actorable([
            'userCallback' => function () {
                return 'me';
            }
        ]));

        $model = $collection->newInstance();
        $model->save();

        $this->assertEquals('me', $model['$created_by']);
        $this->assertEquals('me', $model['$updated_by']);

        $model['$created_by'] = $model['$updated_by'] = 'somebody';
        $model->save();

        $this->assertEquals('somebody', $model['$created_by']);
        $this->assertEquals('me', $model['$updated_by']);
    }
}
