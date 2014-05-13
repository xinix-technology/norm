<?php

namespace Norm\Observer;

class Hashed
{
    protected $options = array(
        'algo' => PASSWORD_BCRYPT,
        'options' => array(
            'cost' => 10
        ),
    );

    public function __construct($options = array())
    {
        $this->options = array_merge_recursive($this->options, $options);
    }

    public function saving($model)
    {
        foreach ($this->options['fields'] as $field) {
            $info = password_get_info($model->get($field));
            if ($info['algo'] == 0) {
                // needs to be rehashed
                $model->set(
                    $field,
                    password_hash($model->get($field), $this->options['algo'], $this->options['options'])
                );
            }
        }
    }
}
