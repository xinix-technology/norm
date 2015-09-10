<?php

namespace Norm\Observer;

use Norm\Norm;
use Norm\Schema\NormArray;

class Historical
{
    public function initialized($collection)
    {
        $schema = NormArray::create('$history')->set('transient', true)->read(function ($model) {
            return Norm::factory($model->getClass().'History')
                ->find(array('model_id' => $model['$id']))->sort(array('$created_time' => -1))
                ->toArray(true);
        });
        $collection->schema('$history', $schema);
    }

    public function saved($model)
    {
        $histCollection = Norm::factory($model->getClass().'History');
        $newValues = $model->dump();
        $oldValues = $model->previous();

        if ($model->isNew()) {
            $history = $histCollection->newInstance();
            $history['model_id'] = $model['$id'];
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

                if ($value instanceof \Norm\Type\Collection && $value->compare($old) == 0) {
                    continue;
                } elseif ($old instanceof \Norm\Type\Collection && $old->compare($value) == 0) {
                    continue;
                } elseif ($value == $old) {
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

                if ($value instanceof \Norm\Type\Collection && $value->compare($new) == 0) {
                    continue;
                } elseif ($new instanceof \Norm\Type\Collection && $new->compare($value) == 0) {
                    continue;
                } elseif ($value == $new) {
                    continue;
                }

                $delta[$key] = array(
                    'old' => $value,
                    'new' => $new,
                );
            }


            foreach ($delta as $key => $value) {
                $histCollection = Norm::factory($model->getClass().'History');
                $history = $histCollection->newInstance();
                $history['model_id'] = $model['$id'];
                $history['type'] = 'update';
                $history['field'] = $key;
                $history['old'] = $value['old'];
                $history['new'] = $value['new'];
                $history->save();
            }
        }
    }

    public function removed($model)
    {
        $histCollection = Norm::factory($model->getClass().'History');

        $history = $histCollection->newInstance();
        $history['model_id'] = $model['$id'];
        $history['type'] = 'remove';
        $history->save();
    }
}
