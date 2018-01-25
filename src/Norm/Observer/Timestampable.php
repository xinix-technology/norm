<?php

namespace Norm\Observer;

use Norm\Schema\NormDateTime;

class Timestampable
{
    public function initialized($collection)
    {
        $collection->schema('$created_time', NormDateTime::create('$created_time'));
        $collection->schema('$updated_time', NormDateTime::create('$updated_time'));
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
