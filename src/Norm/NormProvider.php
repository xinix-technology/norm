<?php

namespace Norm;

class NormProvider {
    protected $app;

    public function initialize($app) {
        $this->app = $app;

        $config = $this->app->config('norm.databases');

        Norm::init($config);
    }
}