<?php

namespace Norm\Provider;

/**
 * Norm\NormProvider
 *
 * Norm provider for Bono web application framework
 *
 */
class NormProvider {

    /**
     * Bono application context
     * @var Bono\App
     */
    protected $app;

    /**
     * Initialize the provider
     * @param  Bono\App    $app Bono application context
     */
    public function initialize($app) {
        $this->app = $app;

        $dbConfig = $this->app->config('norm.databases');
        $schemaConfig = $this->app->config('norm.schemas');

        \Norm\Norm::init($dbConfig, $schemaConfig);
    }
}
