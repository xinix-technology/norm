<?php namespace Norm\Observer;

class Hashable
{
    protected $options = array(
        'fields'  => array('password'),
        'algo'    => PASSWORD_BCRYPT,
        'options' => array(
            'cost' => 10
        ),
    );

    public function __construct($options = array())
    {
        $this->options = array_merge($this->options, $options);
    }

    public function saving($model)
    {
        foreach ($this->options['fields'] as $field) {
            $password = (string) $model[$field];
            $info     = password_get_info($password);

            if ($info['algo'] === 0) {
                // needs to be rehashed
                $model[$field] = password_hash($password, $this->options['algo'], $this->options['options']);
            }
        }
    }
}
