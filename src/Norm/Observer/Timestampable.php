<?php

namespace Norm\Observer;

use Norm\Schema\DateTime;

class Timestampable
{
    public function initialized($collection)
    {
        $collection->schema('$created_time', DateTime::create('$created_time'));
        $collection->schema('$updated_time', DateTime::create('$updated_time'));
    }

    public function saving($model)
    {
        $now = new \DateTime();

        if ($model->isNew()) {
            $model['$updated_time'] = $now;
            $model['$created_time'] = $now;
        } else {
            $model['$updated_time'] = $now;
        }
    }
}
