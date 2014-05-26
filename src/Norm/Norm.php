<?php

/**
 * Norm - (not) ORM Framework
 *
 * MIT LICENSE
 *
 * Copyright (c) 2013 PT Sagara Xinix Solusitama
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 *
 */
namespace Norm;

use Norm\Connection;

/**
 * Static class to bootstrap Norm framework functionality.
 *
 * To get collection with name specified from default connection:
 *
 * <code>
 * Norm::factory('Content')
 * </code>
 *
 */
class Norm
{

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
     * Collection configuration
     * @var array
     */
    protected static $collectionConfig;

    protected static $options = array();

    /**
     * Initialize framework from configuration. First connection registered from
     * config will be the default connection.
     *
     * @param  array  $config [description]
     */
    public static function init($config, $collectionConfig = array())
    {
        $first = null;

        static::$collectionConfig = $collectionConfig;

        if (empty($config)) {
            return;
        }

        foreach ($config as $key => $value) {
            $value['name'] = $key;

            if (!isset($value['driver'])) {
                throw new \Exception(
                    '[Norm] Cannot instantiate connection "'.$key.
                    '", Driver "'.@$value['driver'].'" not found!'
                );
            }
            $Driver = $value['driver'];

            static::$connections[$key] = new $Driver($value);
            if (!static::$connections[$key] instanceof Connection) {
                throw new \Exception('Norm connection ['.$key.'] should be instance of Connection');
            }

            if (!$first) {
                $first = $key;
            }
        }

        if (!static::$defaultConnection) {
            static::$defaultConnection = $first;
        }

    }

    public static function options($key, $value = ':get:')
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                static::$options($k, $v);
            }
            return;
        }

        if ($value === ':get:') {
            return isset(static::$options[$key]) ? static::$options[$key] : null;
        }

        static::$options[$key] = $value;
    }

    public static function registerCollection($key, $options)
    {
        static::$collectionConfig['mapping'][$key] = $options;
    }

    public static function createCollection($options)
    {
        $defaultConfig = isset(static::$collectionConfig['default'])
            ? static::$collectionConfig['default']
            : array();

        $config = null;

        if (isset(static::$collectionConfig['mapping'][$options['name']])) {
            $config =static::$collectionConfig['mapping'][$options['name']];
        } else {
            if (isset(static::$collectionConfig['resolvers']) && is_array(static::$collectionConfig['resolvers'])) {
                foreach (static::$collectionConfig['resolvers'] as $resolver => $resolverOpts) {
                    if (is_string($resolverOpts)) {
                        $resolver = $resolverOpts;
                        $resolverOpts = array();
                    }

                    $resolver = new $resolver($resolverOpts);
                    $config = $resolver->resolve($options);
                    if (isset($config)) {
                        break;
                    }
                }
            }
        }


        if (!isset($config)) {
            $config = array();
        }

        $config = array_merge_recursive($defaultConfig, $config);

        $options = array_merge_recursive($config, $options);

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
    public static function getConnection($connectionName = '')
    {
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
    public static function reset()
    {
        static::$defaultConnection = null;
    }

    /**
     * All static call of method will be straight through to the default
     * connection method call with the same method name.
     *
     * @param  string $method     Method name
     * @param  array  $parameters Parameters
     * @return mixed              Return value
     */
    public static function __callStatic($method, $parameters)
    {
        $connection = static::getConnection();
        if ($connection) {
            return call_user_func_array(array($connection, $method), $parameters);
        } else {
            throw new \Exception("[Norm] No connection exists.");
        }
    }
}
