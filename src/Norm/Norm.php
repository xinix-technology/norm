<?php
namespace Norm;

class Norm {
    public static $connections = array();
    public static $defaultConnection = NULL;

    public static function init($config) {
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
    }

    public static function getConnection($connectionName = '') {
        if (!$connectionName) {
            $connectionName = static::$defaultConnection;
        }
        if (isset(static::$connections[$connectionName])) {
            return static::$connections[$connectionName];
        }
    }

    public static function __callStatic($method, $parameters) {
        $connection = static::getConnection();
        if ($connection) {
            return call_user_func_array(array($connection, $method), $parameters);
        }
    }

}