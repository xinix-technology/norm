<?php

namespace Norm\Observer;

use Norm\Schema\Reference;

class Ownership
{
    // public function initialized($collection)
    // {
    //     $collection->schema('$created_by', Reference::create('$created_by')->to('User'));
    //     $collection->schema('$updated_by', Reference::create('$updated_by')->to('User'));
    // }

    public function saving($model)
    {
        if ($model->isNew()) {
            $model['$updated_by'] = $model['$created_by'] = @$_SESSION['user']['$id'];
        } else {
            $model['$updated_by'] = @$_SESSION['user']['$id'];
        }

    }
}
