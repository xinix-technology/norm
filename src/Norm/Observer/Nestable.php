<?php

namespace Norm\Observer;

class Nestable
{
    public function saved($model)
    {
        $this->rebuildTree($model->getCollection(), null, 0);
    }

    public function removing($model)
    {
        $collection = $model->getCollection();
        $entries = $collection->find(array('$lft!gt' => $model['$lft'], '$rgt!lt' => $model['$rgt']));

        foreach ($entries as $entry) {
            $collection->connection->remove($entry);
        }
    }

    public function removed($model)
    {
        $this->rebuildTree($model->getCollection(), null, 0);
    }

    protected function rebuildTree($collection, $parent, $left)
    {
        $right = $left + 1;

        // get all children of this node
        $result = $collection->find(array('parent' => $parent));

        foreach ($result as $row) {
            // recursive execution of this function for each
            // child of this node
            // $right is the current right value, which is
            // incremented by the rebuild_tree function
            $right = $this->rebuildTree($collection, $row['$id'], $right);
        }

        // we've got the left value, and now that we've processed
        // the children of this node we also know the right value
        if (isset($parent)) {
            $model = $collection->findOne($parent);
            $model['$lft'] = $left;
            $model['$rgt'] = $right;
            // save without save function to avoid observers
            $collection->save($model, array('observer' => false));
        }

        // return the right value of this node + 1
        return $right + 1;
    }
}
