<?php

namespace Norm\Observer;

use Norm\Norm;
use Norm\Schema\ArrayList;
use ROH\Util\Collection as UtilCollection;

class Historable
{
    public function initialize($context)
    {
        $historyField = ArrayList::create()
            ->transient()
            ->withReader(function ($model) use ($context) {
                return $context['collection']->factory($context['collection']->getName().'History')
                    ->find(array('model_id' => $model['$id']))
                    ->sort(array('$created_time' => -1))
                    ->toArray(true);
            });
        $context['collection']->getSchema()->withField('$history', $historyField);
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
            $delta = array();

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

                $delta[$key] = array(
                    'old' => $old,
                    'new' => $value,
                );
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

                $delta[$key] = array(
                    'old' => $value,
                    'new' => $new,
                );
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

    public function remove($context)
    {
        $next($context);

        $histCollection = $context['collection']->factory($context['model']->getName().'History');

        $history = $histCollection->newInstance();
        $history['model_id'] = $context['model']['$id'];
        $history['type'] = 'remove';
        $history->save();
    }
}
