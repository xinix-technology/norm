<?php

namespace Norm\Resolver;

class CollectionResolver
{
    public function resolve($options)
    {
        $app = \App::getInstance();
        $configPath = rtrim($app->config('config.path'), '/').'/collections/'.$options['name'].'.php';
        if (is_readable($configPath)) {
            return include($configPath);
        }
    }
}
