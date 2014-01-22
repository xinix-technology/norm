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
        $dbConfig = $this->app->_config->get('norm.databases');
        $collectionConfig = $this->app->_config->get('norm.collections');

        \Norm\Norm::init($dbConfig, $collectionConfig);
    }
}
