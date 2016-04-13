<?php

namespace Norm\Observer;

use Norm\Repository;
use Norm\Schema\NList;
use ROH\Util\Collection as UtilCollection;

class Historable
{
    public function initialize($context)
    {
        $context['collection']->getSchema()
            ->addField([ NList::class, [
                'options' => [
                    'name' => '$history',
                    'transient' => true,
                ],
            ]])
            ->setReader(function ($model) use ($context) {
                return $context['collection']->factory($context['collection']->getName().'History')
                    ->find(array('model_id' => $model['$id']))
                    ->sort(array('$created_time' => -1))
                    ->toArray(true);
            });
    }

    public function save($context, $next)
    {
        $next($context);

        $histCollection = $context['collection']->factory($context['collection']->getName().'History');
        $newValues = $context['model']->dump();
        $oldValues = $context['model']->previous();

        if ($context['model']->isNew()) {
            $history = $histCollection->newInstance();
            $history['model_id'] = $context['modified']['$id'];
            $history['type'] = 'new';
            $history->save();
        } else {
            $delta = [];

            foreach ($newValues as $key => $value) {
                if ($key[0] === '$') {
                    continue;
                }

                $old = null;
                if (isset($oldValues[$key])) {
                    $old = $oldValues[$key];
                }

                // if ($value instanceof UtilCollection && $value->compare($old) == 0) {
                //     continue;
                // } elseif ($old instanceof UtilCollection && $old->compare($value) == 0) {
                //     continue;
                // } else
                if ($value == $old) {
                    continue;
                }

                $delta[$key] = [
                    'old' => $old,
                    'new' => $value,
                ];
            }
            foreach ($oldValues as $key => $value) {
                if ($key[0] === '$') {
                    continue;
                }

                $new = null;
                if (isset($newValues[$key])) {
                    $new = $newValues[$key];
                }

                // if ($value instanceof   UtilCollection && $value->compare($new) == 0) {
                //     continue;
                // } elseif ($new instanceof   UtilCollection && $new->compare($value) == 0) {
                //     continue;
                // } else
                if ($value == $new) {
                    continue;
                }

                $delta[$key] = [
                    'old' => $value,
                    'new' => $new,
                ];
            }


            foreach ($delta as $key => $value) {
                $histCollection = $context['collection']->factory($context['collection']->getName().'History');
                $history = $histCollection->newInstance();
                $history['model_id'] = $context['model']['$id'];
                $history['type'] = 'update';
                $history['field'] = $key;
                $history['old'] = $value['old'];
                $history['new'] = $value['new'];
                $history->save();
            }
        }
    }

    public function remove($context, $next)
    {
        $next($context);

        $histCollection = $context['collection']->factory($context['model']->getName().'History');

        $history = $histCollection->newInstance();
        $history['model_id'] = $context['model']['$id'];
        $history['type'] = 'remove';
        $history->save();
    }
}
