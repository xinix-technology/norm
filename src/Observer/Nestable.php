<?php

namespace Norm\Observer;

use Norm\Schema\NInteger;

class Nestable
{
    public function initialize($context)
    {
        $schema = $context['collection']->getSchema();
        $schema->addField([ NInteger::class, [
            'name' => '$lft'
        ]]);
        $schema->addField([ NInteger::class, [
            'name' => '$rgt'
        ]]);
    }

    public function save($context, $next)
    {
        $next($context);

        $this->rebuildTree($context['collection'], null, 0);
    }

    public function remove($context, $next)
    {
        if ($context['model']) {
            $entries = $context['collection']->find([
                '$lft!gt' => $context['model']['$lft'],
                '$rgt!lt' => $context['model']['$rgt'],
            ]);

            if ($entries->count()) {
                $entries->remove();
            }
        }

        $next($context);

        $this->rebuildTree($context['collection'], null, 0);
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

        // // we've got the left value, and now that we've processed
        // // the children of this node we also know the right value
        if (isset($parent)) {
            $model = $collection->findOne($parent);
            $model['$lft'] = $left;
            $model['$rgt'] = $right;
            // save without save function to avoid observers
            $collection->save($model, ['observer' => false]);
        }

        // // return the right value of this node + 1
        return $right + 1;
    }
}
