<?php
namespace Norm\Test\Observer;

use Norm\Observer\Nestable;
use Norm\Test\ObserverTestCase;

class NestableTest extends ObserverTestCase
{
    public function testSave()
    {
        $collection = $this->getCollection(new Nestable());

        $parent = $collection->newInstance();
        $parent['name'] = 'parent';
        $parent->save();


        $child1 = $collection->newInstance();
        $parent['name'] = 'child1';
        $child1['parent'] = $parent['$id'];
        $child1->save();

        $child2 = $collection->newInstance();
        $parent['name'] = 'child2';
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
    }
}
