<?php

namespace Norm\Observer;

class Ownership
{
    public function saving($model)
    {
        if ($model->isNew()) {
            $model['$updated_by'] = $model['$created_by'] = @$_SESSION['user']['$id'];
        } else {
            $model['$updated_by'] = @$_SESSION['user']['$id'];
        }

    }
}
