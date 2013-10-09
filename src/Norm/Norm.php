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

    /**
     * Initialize framework from configuration. First connection registered from
     * config will be the default connection.
     *
     * @param  array  $config [description]
     */
    public static function init($config, $schemaConfig = NULL) {
        $first = NULL;

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

        if (!empty($schemaConfig)) {
            Norm::hook('norm.after.factory', function($collection) use ($schemaConfig) {
                if (isset($schemaConfig[$collection->clazz])) {
                    $collection->schema(new Schema($schemaConfig[$collection->clazz]));
                }
            });
        }
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
