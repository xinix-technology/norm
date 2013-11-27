<?php

namespace Norm;

/**
 * Norm\Norm
 *
 * Static class to bootstrap Norm framework functionality.
 *
 */
class Norm {

    /**
     * Register all connections.
     *
     * @var array
     */
    public static $connections = array();

    /**
     * Default connection name. First connection registered will be the default
     * connection.
     *
     * @var string
     */
    public static $defaultConnection = '';

    protected static $collectionConfig;

    /**
     * Initialize framework from configuration. First connection registered from
     * config will be the default connection.
     *
     * @param  array  $config [description]
     */
    public static function init($config, $collectionConfig = array()) {
        $first = NULL;

        static::$collectionConfig = $collectionConfig;

        foreach ($config as $key => $value) {
            $value['name'] = $key;

            $Driver = $value['driver'];

            static::$connections[$key] = new $Driver($value);

            if (!$first) {
                $first = $key;
            }
        }

        if (!static::$defaultConnection) {
            static::$defaultConnection = $first;
        }

    }

    public static function createCollection($options) {
        $defaultConfig = isset(static::$collectionConfig['default']) ? static::$collectionConfig['default'] : array();
        $config = isset(static::$collectionConfig['mapping'][$options['name']]) ? static::$collectionConfig['mapping'][$options['name']] : array();
        $config = array_merge($defaultConfig, $config);

        $options = array_merge($config, $options);


        if (isset($options['collection'])) {
            $Driver = $options['collection'];
            $collection = new $Driver($options);
        } else {
            $collection = new Collection($options);
        }

        return $collection;
    }

    /**
     * Get connection by its connection name, if no connection name provided
     * then the function will return default connection.
     *
     * @param  string $connectionName [description]
     * @return Norm\Connection        [description]
     */
    public static function getConnection($connectionName = '') {
        if (!$connectionName) {
            $connectionName = static::$defaultConnection;
        }
        if (isset(static::$connections[$connectionName])) {
            return static::$connections[$connectionName];
        }
    }

    /**
     * Reset connection registry
     */
    public static function reset() {
        static::$defaultConnection = NULL;
    }

    /**
     * All static call of method will be straight through to the default
     * connection method call with the same method name.
     *
     * @param  string $method     Method name
     * @param  array  $parameters Parameters
     * @return mixed              Return value
     */
    public static function __callStatic($method, $parameters) {
        $connection = static::getConnection();
        if ($connection) {
            return call_user_func_array(array($connection, $method), $parameters);
        }
    }

}
