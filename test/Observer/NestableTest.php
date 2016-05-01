<?php
namespace Norm\Test\Observer;

use Norm\Observer\Nestable;
use PHPUnit_Framework_TestCase;

class NestableTest extends AbstractObserverTest
{
    public function testSaveAndRemove()
    {
        $collection = $this->getCollection(new Nestable());

        $parent = $collection->newInstance();
        $parent['name'] = 'parent';
        $parent->save();

        $child1 = $collection->newInstance();
        $child1['name'] = 'child1';
        $child1['parent'] = $parent['$id'];
        $child1->save();

        $child2 = $collection->newInstance();
        $child1['name'] = 'child2';
        $child2['parent'] = $parent['$id'];
        $child2->save();

        foreach ($collection->find() as $entry) {
            switch ($entry['$id']) {
                case $parent['$id']:
                    $this->assertEquals(1, $entry['$lft']);
                    $this->assertEquals(6, $entry['$rgt']);
                    break;
                case $child1['$id']:
                    $this->assertEquals(2, $entry['$lft']);
                    $this->assertEquals(3, $entry['$rgt']);
                    break;
                case $child2['$id']:
                    $this->assertEquals(4, $entry['$lft']);
                    $this->assertEquals(5, $entry['$rgt']);
                    break;
            }
        }

        $parent = $collection->findOne($parent['$id']);
        $parent->remove();
        $this->assertEquals($collection->find()->count(), 0);
    }
}
