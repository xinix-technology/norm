<?php

namespace Norm\Observer;

class DefaultValueable
{

    public function saving($model)
    {
        if (!$model->isNew()) {
            return;
        }

        $schema = $model->schema();
        foreach ($schema as $fieldSchema) {
            if (empty($model[$fieldSchema['name']]) && !is_null($fieldSchema['defaultValue'])) {
                $model[$fieldSchema['name']] = $fieldSchema['defaultValue'];
            }
        }
    }
}
