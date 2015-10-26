<?php
namespace Norm;

use Exception;
use InvalidArgumentException;
use ROH\Util\Thing;
use ROH\Util\Options;

class Norm
{
    protected $useConnection;

    protected $default;

    protected $connections = [];

    protected $resolvers = [];

    // protected $mapping = [];

    protected $translator;

    protected $renderer;

    protected $collections = [];

    public function __construct($options = array())
    {
        if (isset($options['connections'])) {
            foreach ($options['connections'] as $id => $connection) {
                $this->add($id, (new Thing($connection))->getHandler());
            }
        }

        if (isset($options['collections'])) {
            if (isset($options['collections']['default'])) {
                $this->setDefault($options['collections']['default']);
            }

            if (isset($options['collections']['resolvers']) && is_array($options['collections']['resolvers'])) {
                foreach ($options['collections']['resolvers'] as $resolver) {
                    $this->addResolver($resolver);
                }
            }
        }
    }

    public function add($id, Connection $connection)
    {
        $this->connections[$id] = $connection;
        $connection->withId($id);
        if (is_null($this->useConnection)) {
            $this->useConnection = $id;
        }

        return $this;
    }

    public function getConnection($id = null)
    {
        return isset($this->connections[$id ?: $this->useConnection]) ?
            $this->connections[$id ?: $this->useConnection] :
            null;
    }

    public function addResolver($resolver)
    {
        $this->resolvers[] = (new Thing($resolver))->getHandler();
        return $this;
    }

    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    public function factory($collectionId, $connectionId = '')
    {
        if (!is_string($collectionId) || !is_string($connectionId)) {
            throw new InvalidArgumentException('Collection and Connection Id must be string');
        }

        $connection = $this->getConnection($connectionId ?: $this->useConnection);
        if (is_null($connection)) {
            throw new Exception('No connection available to create collection');
        }

        if (!isset($this->collections[$collectionId])) {
            $options = Options::create($this->default)
                ->merge([
                    'name' => $collectionId,
                ]);

            $found = false;
            foreach ($this->resolvers as $resolver) {
                $resolved = $resolver($collectionId);
                if (isset($resolved)) {
                    $options->merge($resolved);
                    $found = true;
                    break;
                }
            }

            $this->collections[$collectionId] = [
                'proto' => new Collection($this, $options),
                'mapping' => [],
            ];
        }

        if (!isset($this->collections[$collectionId]['mapping'][$connectionId])) {
            $this->collections[$collectionId]['mapping'][$connectionId] = $this->collections[$collectionId]['proto']
                ->withConnection($connection);
        }

        return $this->collections[$collectionId]['mapping'][$connectionId];
    }

    public function translate($message)
    {
        return empty($this->translator) ? $message : call_user_func_array($this->translator, func_get_args());
    }

    public function render($template, $context = array())
    {
        try {
            if (is_null($this->renderer)) {
                throw new Exception('Unset renderer for Norm');
            }
            return $this->renderer($template, $context);
        } catch (Exception $e) {
            $templateFile = __DIR__.'/../templates/'.$template.'.php';
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

    public function __invoke($name, $connectionId = null)
    {
        return $this->factory($name, $connectionId);
    }

    public function __debugInfo()
    {
        return [
            'use' => $this->useConnection,
            'connections' => $this->connections,
        ];
    }
}

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2015 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
// class Norm
// {
//     /**
//      * Is norm initialized?
//      * @var boolean
//      */
//     public static $initialized = false;

//     /**
//      * Register all connections.
//      *
//      * @var array
//      */
//     public static $connections = array();

//     /**
//      * Default connection id. First connection registered will be the default connection.
//      *
//      * @var string
//      */
//     public static $defaultConnection = '';

//     /**
//      * Collection configuration
//      *
//      * @var array
//      */
//     protected static $collectionConfig;

//     /**
//      * Options
//      *
//      * @var array
//      */
//     protected static $options = array();

//     /**
//      * Initialize framework from configuration. First connection registered from config will be the default connection.
//      *
//      * @param array $config
//      *
//      * @return void
//      */
//     public static function init($options = [])
//     {
//         if (static::$initialized) {
//             return;
//         }

//         static::$initialized = true;

//         static::$options = $options;

//         if (isset($options['collections'])) {
//             static::$collectionConfig = $options['collections'];
//         }
//     }

//     /**
//      * Get the option of Norm configuration.
//      *
//      * @method options
//      *
//      * @param string $key
//      * @param string $value
//      *
//      * @return mixed
//      */
//     public static function options($key, $value = null)
//     {

//         if (func_num_args() === 1) {
//             if (is_array($key)) {
//                 foreach ($key as $k => $v) {
//                     static::$options($k, $v);
//                 }
//             } else {
//                 return isset(static::$options[$key]) ? static::$options[$key] : null;
//             }
//         } else {
//             static::$options[$key] = $value;
//         }
//     }

//     public static function render($template, $context = array())
//     {
//         try {
//             $renderer = Norm::options('renderer');
//             if (is_null($renderer)) {
//                 throw new Exception('Unset renderer for Norm');
//             }
//             return $renderer($template, $context);
//         } catch (Exception $e) {
//             ob_end_clean();
//             $templateFile = __DIR__.'/../../templates/'.$template.'.php';
//             if (is_readable($templateFile)) {
//                 ob_start();
//                 extract($context);
//                 include $templateFile;
//                 $html = ob_get_clean();
//                 return $html;
//             } else {
//                 throw $e;
//             }
//         }
//     }

//     /**
//      * Register collection on the fly.
//      *
//      * @method registerCollection
//      *
//      * @param string $key
//      * @param array $options
//      *
//      * @return void
//      */
//     public static function registerCollection($key, $options)
//     {
//         static::$collectionConfig['mapping'][$key] = $options;
//     }

//     /**
//      * Create collection by configuration.
//      *
//      * @method createCollection
//      *
//      * @param array $options
//      *
//      * @return mixed|Norm\Collection
//      */
//     public static function createCollection($options)
//     {
//         $defaultConfig = isset(static::$collectionConfig['default'])
//             ? static::$collectionConfig['default']
//             : array();

//         $config = null;

//         if (isset(static::$collectionConfig['mapping'][$options['name']])) {
//             $config =static::$collectionConfig['mapping'][$options['name']];
//         } else {
//             if (isset(static::$collectionConfig['resolvers']) and is_array(static::$collectionConfig['resolvers'])) {
//                 foreach (static::$collectionConfig['resolvers'] as $resolver => $resolverOpts) {
//                     if (is_string($resolverOpts)) {
//                         $resolver = $resolverOpts;
//                         $resolverOpts = array();
//                     }

//                     $resolver = new $resolver($resolverOpts);
//                     $config = $resolver->resolve($options);

//                     if (isset($config)) {
//                         break;
//                     }
//                 }
//             }
//         }


//         if (!isset($config)) {
//             $config = array();
//         }

//         $config = array_merge_recursive($defaultConfig, $config);

//         $options = array_merge_recursive($config, $options);

//         if (isset($options['collection'])) {
//             $Driver = $options['collection'];
//             $collection = new $Driver($options);
//         } else {
//             $collection = new Collection($options);
//         }

//         return $collection;
//     }

//     /**
//      * Get connection by its connection name, if no connection name provided
//      * then the function will return default connection.
//      *
//      * @param string $connectionName
//      *
//      * @return Norm\Connection
//      */
//     public static function getConnection($connectionName = '')
//     {
//         if (!$connectionName) {
//             $connectionName = static::$defaultConnection;
//         }
//         if (isset(static::$connections[$connectionName])) {
//             return static::$connections[$connectionName];
//         }
//     }

//     /**
//      * Register new connection
//      * @param  [type] $name       [description]
//      * @param  [type] $connection [description]
//      * @return [type]             [description]
//      */
//     public static function registerConnection($name, $connection)
//     {
//         static::$connections[$name] = $connection;

//         if (!static::$connections[$name] instanceof Connection) {
//             throw new Exception('Norm connection ['.$name.'] should be instance of Connection');
//         }

//         if (!static::$defaultConnection) {
//             static::$defaultConnection = $name;
//         }
//     }

//     /**
//      * Reset connection registry
//      *
//      * @return void
//      */
//     public static function reset()
//     {
//         static::$defaultConnection = null;
//     }

//     public static function translate($message)
//     {
//         $translator = Norm::options('translator');
//         return empty($translator) ? $message : call_user_func_array($translator, func_get_args());
//     }

//     /**
//      * All static call of method will be straight through to the default
//      * connection method call with the same method name.
//      *
//      * @param  string $method     Method name
//      * @param  array  $parameters Parameters
//      * @return mixed              Return value
//      */
//     public static function __callStatic($method, $parameters)
//     {
//         $connection = static::getConnection();
//         if ($connection) {
//             return call_user_func_array(array($connection, $method), $parameters);
//         } else {
//             throw new Exception("[Norm] No connection exists.");
//         }
//     }
// }
