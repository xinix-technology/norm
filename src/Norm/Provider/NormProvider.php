<?php

namespace Norm\Provider;

/**
 * Norm\NormProvider
 *
 * Norm provider for Bono web application framework
 *
 */
class NormProvider extends \Bono\Provider\Provider {
    /**
     * Initialize the provider
     */
    public function initialize() {
        $dbConfig = $this->app->config('norm.databases');
        $collectionConfig = $this->app->config('norm.collections');

        \Norm\Norm::init($dbConfig, $collectionConfig);
    }
}
