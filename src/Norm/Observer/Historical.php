<?php

namespace Norm\Observer;

use \Norm\Norm;

class Historical
{
    public function saved($model)
    {
        $histCollection = Norm::factory($model->clazz.'History');
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

                if ($value == $new) {
                    continue;
                }

                $delta[$key] = array(
                    'old' => $value,
                    'new' => $new,
                );
            }

            foreach ($delta as $key => $value) {
                $histCollection = Norm::factory($model->clazz.'History');
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
        $histCollection = Norm::factory($model->clazz.'History');

        $history = $histCollection->newInstance();
        $history['model_id'] = $model['$id'];
        $history['type'] = 'remove';
        $history->save();
    }
}
