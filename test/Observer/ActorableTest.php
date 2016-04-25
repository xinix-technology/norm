<?php
namespace Norm\Test\Observer;

use Norm\Observer\Actorable;
use Norm\Exception\NormException;

class ActorableTest extends AbstractObserverTest
{
    public function testConstruct()
    {
        try {
            $collection = $this->getCollection(new Actorable([
                'userCallback' => 'foo'
            ]));
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'Actorable needs userCallback as callable') {
                throw $e;
            }
        }
    }

    public function testSave()
    {
        $_SESSION['user']['$id'] = 'session-user';

        $collection = $this->getCollection(new Actorable());

        $model = $collection->newInstance();
        $model->save();

        $this->assertEquals('session-user', $model['$created_by']);
        $this->assertEquals('session-user', $model['$updated_by']);

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
