<?php

namespace Norm\Observer;

class Timestampable
{
    public function saving($model)
    {
        if (is_null($model['$id'])) {
            $model['$updated_time'] = $model['$created_time'] = $model->prepare(
                null,
                new \DateTime(),
                \Norm\Schema\DateTime::create()
            );
        } else {
            $model['$updated_time'] = $model->prepare(null, new \DateTime(), \Norm\Schema\DateTime::create());
        }
    }
}
