<?php
namespace Norm;

use Norm\Exception\NormException;
use ROH\Util\Options;
use ROH\Util\Injector;

class Repository extends Injector
{
    const TEMPLATE_PATH = __DIR__ . '/../templates/';

    protected $useConnection;

    protected $default;

    protected $connections = [];

    protected $resolvers = [];

    protected $translator;

    protected $renderer;

    protected $collections = [];

    protected $attributes = [];

    /**
     * [__construct description]
     * @param array $options [description]
     */
    public function __construct(array $options = [])
    {
        $this->singleton(Repository::class, $this);

        if (isset($options['attributes'])) {
            $this->attributes = $options['attributes'];
        }

        if (isset($options['connections'])) {
            foreach ($options['connections'] as $meta) {
                $this->add($this->resolve($meta));
            }
        }

        if (isset($options['collections'])) {
            if (isset($options['collections']['default'])) {
                $this->setDefault($options['collections']['default']);
            }

            if (isset($options['collections']['resolvers'])) {
                foreach ($options['collections']['resolvers'] as $resolver) {
                    $this->addResolver($this->resolve($resolver));
                }
            }
        }

        if (isset($options['renderer'])) {
            $this->renderer = $options['renderer'];
        }

        if (isset($options['translator'])) {
            $this->translator = $options['translator'];
        } else {
            $this->translator = 'sprintf';
        }
    }

    /**
     * [add description]
     * @param Connection $connection [description]
     */
    public function add(Connection $connection)
    {
        $id = $connection->getId();
        $this->connections[$id] = $connection;
        if (is_null($this->useConnection)) {
            $this->useConnection = $id;
        }

        return $this;
    }

    /**
     * [getConnection description]
     * @param  string     $id [description]
     * @return Connection     [description]
     */
    public function getConnection($id = '')
    {
        if (!is_null($id) && !is_string($id)) {
            throw new NormException('Connection id must be string');
        }

        return isset($this->connections[$id ?: $this->useConnection]) ?
            $this->connections[$id ?: $this->useConnection] :
            null;
    }

    /**
     * [getAttribute description]
     * @param  string $key [description]
     * @return mixed       [description]
     */
    public function getAttribute($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * [setAttribute description]
     * @param string $key   [description]
     * @param string $value [description]
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * [addResolver description]
     * @param callable $resolver [description]
     */
    public function addResolver(callable $resolver)
    {
        $this->resolvers[] = $resolver;
        return $this;
    }

    /**
     * [setDefault description]
     * @param array $default [description]
     */
    public function setDefault(array $default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * [factory description]
     * @param  string     $collectionId [description]
     * @param  string     $connectionId [description]
     * @return Collection               [description]
     */
    public function factory($collectionId, $connectionId = '')
    {
        if (!is_string($collectionId) || !is_string($connectionId)) {
            throw new NormException('Collection and Connection Id must be string');
        }

        $connection = $this->getConnection($connectionId ?: $this->useConnection);
        if (is_null($connection)) {
            throw new NormException('No connection available to create collection');
        }

        $collectionSignature = $collectionId . '.' . $connection->getId();
        if (!isset($this->collections[$collectionSignature])) {
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

            $this->collections[$collectionSignature] = $this->resolve(Collection::class, [
                'connection' => $connection,
                'options' => $options
            ]);
        }

        return $this->collections[$collectionSignature];
    }

    /**
     * [translate description]
     * @param  string $message [description]
     * @return string          [description]
     */
    public function translate($message)
    {
        if (!is_string($message)) {
            throw new NormException('Message to translate must be string');
        }

        $translate = $this->translator;
        return call_user_func_array($translate, func_get_args());
    }

    /**
     * [render description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @return string           [description]
     */
    public function render($template, array $data = [])
    {
        if (!is_string($template)) {
            throw new NormException('Template to render must be string');
        }

        if (isset($this->renderer)) {
            $render = $this->renderer;
            return $render($template, $data);
        } else {
            $templateFile = static::TEMPLATE_PATH . $template . '.php';
            if (is_readable($templateFile)) {
                ob_start();
                extract($data);
                include $templateFile;
                return ob_get_clean();
            } else {
                throw new NormException('Template not found, ' . $template);
            }
        }
    }

    /**
     * [setRenderer description]
     * @param callable $renderer [description]
     */
    public function setRenderer(callable $renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    /**
     * [setTranslator description]
     * @param callable $translator [description]
     */
    public function setTranslator(callable $translator)
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * [__invoke description]
     * @param  string $collectionId     [description]
     * @param  string $connectionId     [description]
     * @return Collection               [description]
     */
    public function __invoke($collectionId, $connectionId = '')
    {
        return $this->factory($collectionId, $connectionId);
    }

    /**
     * [__debugInfo description]
     * @return array [description]
     */
    public function __debugInfo()
    {
        return [
            'connections' => $this->connections,
            'use' => $this->useConnection,
        ];
    }
}
