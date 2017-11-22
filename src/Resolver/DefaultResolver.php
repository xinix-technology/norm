<?php

namespace Norm\Resolver;

use ROH\Util\Options;

class DefaultResolver
{
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = (new Options([
                'resolvePaths' => [ '../config/collections' ]
            ]))
            ->merge($options);
    }

    public function __invoke($name)
    {
        foreach ($this->options['resolvePaths'] as $path) {
            $configPath = $path. '/' . $name . '.php';
            if (is_readable($configPath)) {
                return include($configPath);
            }
        }
    }
}
