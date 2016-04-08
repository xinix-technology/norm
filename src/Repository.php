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

        $this->translator = isset($options['translator']) ?
            $options['translator'] :
            'sprintf';
    }

    public function add(Connection $connection)
    {
        $id = $connection->getId();
        $this->connections[$id] = $connection;
        if (is_null($this->useConnection)) {
            $this->useConnection = $id;
        }

        return $this;
    }

    public function getConnection($id = '')
    {
        if (!is_null($id) && !is_string($id)) {
            throw new NormException('Connection id must be string');
        }

        return isset($this->connections[$id ?: $this->useConnection]) ?
            $this->connections[$id ?: $this->useConnection] :
            null;
    }

    public function getAttribute($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    public function addResolver($resolver)
    {
        if (!is_callable($resolver)) {
            throw new NormException('Resolver must be callable');
        }

        $this->resolvers[] = $resolver;
        return $this;
    }

    public function setDefault(array $default)
    {
        $this->default = $default;
        return $this;
    }

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
            // $this->collections[$collectionSignature] = (new Collection($this, $options))->withConnection($connection);
        }

        return $this->collections[$collectionSignature];
    }

    // maybe we shouldnt use own renderer or delegate
    public function translate($message)
    {
        if (!is_string($message)) {
            throw new NormException('Message to translate must be string');
        }

        $translate = $this->translator;
        return call_user_func_array($translate, func_get_args());
    }

    public function render($template, array $data = [])
    {
        if (!is_string($template)) {
            throw new NormException('Template to render must be string');
        }

        if (isset($this->renderer)) {
            $render = $this->renderer;
            return $render($template, $data);
        } else {
            return $this->defaultRender($template, $data);
        }
    }

    public function defaultRender($template, array $data = []) {
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

    public function setRenderer(callable $renderer)
    {
        $this->renderer = $renderer;
        return $this;
    }

    public function setTranslator(callable $translator)
    {
        $this->translator = $translator;
        return $this;
    }

    public function __invoke($name, $connectionId = '')
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
