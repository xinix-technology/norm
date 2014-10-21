<?php

namespace Norm\Resolver;

class CollectionResolver
{

    protected $options = array(
        'collectionPath' => null,
    );

    public function __construct($options) {
        $this->options = array_merge($this->options, $options);
    }

    public function resolve($options)
    {
        $app = \App::getInstance();

        $configPath = null;

        if (isset($this->options['collectionPath'])) {
            if (is_callable($this->options['collectionPath'])) {
                $configPath = call_user_func($this->options['collectionPath']);
            } else {
                $configPath = $this->options['collectionPath'];
            }
        }

        if (!is_readable($configPath)) {
            $configPath = rtrim($app->config('config.path'), '/').'/collections/'.$options['name'].'.php';
        }

        if (is_readable($configPath)) {
            return include($configPath);
        }
    }
}
