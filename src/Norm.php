<?php namespace Norm;

use Exception;
use Norm\Connection;

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Norm
{
    /**
     * Is norm initialized?
     * @var boolean
     */
    public static $initialized = false;

    /**
     * Register all connections.
     *
     * @var array
     */
    public static $connections = array();

    /**
     * Default connection name. First connection registered will be the default connection.
     *
     * @var string
     */
    public static $defaultConnection = '';

    /**
     * Collection configuration
     *
     * @var array
     */
    protected static $collectionConfig;

    /**
     * Options
     *
     * @var array
     */
    protected static $options = array();

    /**
     * Initialize framework from configuration. First connection registered from config will be the default connection.
     *
     * @param array $config
     *
     * @return void
     */
    public static function init($options = [])
    {
        if (static::$initialized) {
            return;
        }

        static::$initialized = true;

        static::$options = $options;

        if (isset($options['collections'])) {
            static::$collectionConfig = $options['collections'];
        }
    }

    /**
     * Get the option of Norm configuration.
     *
     * @method options
     *
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    public static function options($key, $value = null)
    {

        if (func_num_args() === 1) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    static::$options($k, $v);
                }
            } else {
                return isset(static::$options[$key]) ? static::$options[$key] : null;
            }
        } else {
            static::$options[$key] = $value;
        }
    }

    public static function render($template, $context = array())
    {
        try {
            $renderer = Norm::options('renderer');
            if (is_null($renderer)) {
                throw new \Exception('Unset renderer for Norm');
            }
            return $renderer($template, $context);
        } catch (\Exception $e) {
            ob_end_clean();
            $templateFile = __DIR__.'/../../templates/'.$template.'.php';
            if (is_readable($templateFile)) {
                ob_start();
                extract($context);
                include $templateFile;
                $html = ob_get_clean();
                return $html;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Register collection on the fly.
     *
     * @method registerCollection
     *
     * @param string $key
     * @param array $options
     *
     * @return void
     */
    public static function registerCollection($key, $options)
    {
        static::$collectionConfig['mapping'][$key] = $options;
    }

    /**
     * Create collection by configuration.
     *
     * @method createCollection
     *
     * @param array $options
     *
     * @return mixed|\Norm\Collection
     */
    public static function createCollection($options)
    {
        $defaultConfig = isset(static::$collectionConfig['default'])
            ? static::$collectionConfig['default']
            : array();

        $config = null;

        if (isset(static::$collectionConfig['mapping'][$options['name']])) {
            $config =static::$collectionConfig['mapping'][$options['name']];
        } else {
            if (isset(static::$collectionConfig['resolvers']) and is_array(static::$collectionConfig['resolvers'])) {
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
     * @param string $connectionName
     *
     * @return \Norm\Connection
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
     * Register new connection
     * @param  [type] $name       [description]
     * @param  [type] $connection [description]
     * @return [type]             [description]
     */
    public static function registerConnection($name, $connection)
    {
        static::$connections[$name] = $connection;

        if (!static::$connections[$name] instanceof Connection) {
            throw new Exception('Norm connection ['.$name.'] should be instance of Connection');
        }

        if (!static::$defaultConnection) {
            static::$defaultConnection = $name;
        }
    }

    /**
     * Reset connection registry
     *
     * @return void
     */
    public static function reset()
    {
        static::$defaultConnection = null;
    }

    public static function translate($message)
    {
        $translator = Norm::options('translator');
        return empty($translator) ? $message : call_user_func_array($translator, func_get_args());
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
            throw new Exception("[Norm] No connection exists.");
        }
    }
}
