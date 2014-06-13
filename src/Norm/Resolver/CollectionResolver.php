<?php

namespace Norm\Resolver;

use Bono\App;

class CollectionResolver
{
    public function resolve($options)
    {
        $collectionsPath = 'collections';

        if (isset($options['collections.path'])) {
            $collectionsPath = $options['collections.path'];
        }

        $app = App::getInstance();
        $configPath = rtrim($app->config('config.path'), '/').'/'.$collectionsPath.'/'.$options['name'].'.php';

        if (is_readable($configPath)) {
            return include($configPath);
        }
    }
}
