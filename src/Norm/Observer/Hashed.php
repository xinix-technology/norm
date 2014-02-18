<?php

namespace Norm\Observer;

class Hashed {
    protected $options = array();

    public function __construct($options = array()) {
        $this->options = $options;

        $this->options['fields'] = array('password');
        $this->options['algo'] = PASSWORD_BCRYPT;
        $this->options['options'] = array('cost' => 10);

        foreach ($options as $key => $option ) {
            if (is_array($option)) {
                $this->options[$key] = array_unique(array_merge($this->options[$key], $option));
            } else {
                $this->options[$key] = $option;
            }
        }
    }

    public function saving($model) {
        foreach ($this->options['fields'] as $field) {
            $info = password_get_info($model[$field]);
            if ($info['algo'] == 0) {
                // needs to be rehashed
                $model[$field] = password_hash($model[$field], $this->options['algo'], $this->options['options']);
            }
        }
    }

}
