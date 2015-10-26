<?php
namespace Norm;

use Exception;
use InvalidArgumentException;
use Norm\Exception\NormException;
use ROH\Util\Thing;
use ROH\Util\Options;

class Norm
{
    protected $useConnection;

    protected $default;

    protected $connections = [];

    protected $resolvers = [];

    protected $translator;

    protected $renderer;

    protected $collections = [];

    public function __construct(array $options = [])
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
                    $this->addResolver((new Thing($resolver))->getHandler());
                }
            }
        }
    }

    public function add($id, Connection $connection)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException('Connection id must be string');
        }

        $this->connections[$id] = $connection;
        $connection->withId($id);
        if (is_null($this->useConnection)) {
            $this->useConnection = $id;
        }

        return $this;
    }

    public function getConnection($id = '')
    {
        if (!is_null($id) && !is_string($id)) {
            throw new InvalidArgumentException('Connection id must be string');
        }

        return isset($this->connections[$id ?: $this->useConnection]) ?
            $this->connections[$id ?: $this->useConnection] :
            null;
    }

    public function addResolver($resolver)
    {
        if (!is_callable($resolver)) {
            throw new InvalidArgumentException('Resolver must be callable');
        }

        $this->resolvers[] = (new Thing($resolver))->getHandler();
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
            throw new InvalidArgumentException('Collection and Connection Id must be string');
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

            $this->collections[$collectionSignature] = (new Collection($this, $options))->withConnection($connection);
        }

        return $this->collections[$collectionSignature];
    }

    public function translate($message)
    {
        if (!is_string($message)) {
            throw new InvalidArgumentException('Message to translate must be string');
        }

        return empty($this->translator) ? $message : call_user_func_array($this->translator, func_get_args());
    }

    public function render($template, array $context = [])
    {
        if (!is_string($template)) {
            throw new InvalidArgumentException('Template to render must be string');
        }

        try {
            if (is_null($this->renderer)) {
                throw new NormException('Unset renderer for Norm');
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
