<?php
namespace Norm;

use Norm\Exception\NormException;
use ROH\Util\Options;
use ROH\Util\Injector;
use Norm\Resolver\DefaultResolver;

class Repository
{
    const TEMPLATE_PATH = __DIR__ . '/../templates/';

    /**
     * [$injector description]
     * @var ROH\Util\Injector
     */
    protected $injector;

    /**
     * [$useConnection description]
     * @var boolean
     */
    protected $useConnection;

    /**
     * [$default description]
     * @var array
     */
    protected $default = [];

    /**
     * [$connections description]
     * @var array
     */
    protected $connections = [];

    /**
     * [$resolvers description]
     * @var array
     */
    protected $resolvers = [];

    /**
     * [$translator description]
     * @var callable
     */
    protected $translator = 'sprintf';

    /**
     * [$renderer description]
     * @var callable
     */
    protected $renderer;

    /**
     * [$collections description]
     * @var array
     */
    protected $collections = [];

    /**
     * [$attributes description]
     * @var array
     */
    protected $attributes = [];

    /**
     * [__construct description]
     * @param array $attributes [description]
     */
    public function __construct(array $attributes = [], Injector $injector = null)
    {
        $this->attributes = $attributes;
        $this->injector = $injector ?: Injector::getInstance();
    }

    public function resolve($contract, array $args = [])
    {
        return $this->injector->resolve($contract, $args);
    }

    /**
     * [set description]
     * @param Connection $connection [description]
     */
    public function addConnection(Connection $connection)
    {
        $id = $connection->getId();
        $this->connections[$id] = $connection;

        if (null === $this->useConnection) {
            $this->useConnection = $id;
        }

        return $this;
    }

    public function useConnection($id)
    {
        if (!isset($this->connections[$id])) {
            throw new NormException('Unknown connection to use, id: ' . $id);
        }
        $this->useConnection = $id;
    }

    /**
     * [getConnection description]
     * @param  string     $id [description]
     * @return Connection     [description]
     */
    public function getConnection($id = '')
    {
        if (null !== $id && !is_string($id)) {
            throw new NormException('Connection id must be string');
        }

        return isset($this->connections[$id ?: $this->useConnection]) ?
            $this->connections[$id ?: $this->useConnection] :
            null;
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

    public function getResolvers()
    {
        if (empty($this->resolvers)) {
            $this->resolvers[] = new DefaultResolver();
        }
        return $this->resolvers;
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

        $connectionId = $connectionId ?: $this->useConnection;
        $connection = $this->getConnection($connectionId);
        if (null === $connection) {
            throw new NormException('Undefined connection to create collection');
        }

        $collectionSignature = "$connectionId:$collectionId";

        if (!isset($this->collections[$collectionSignature])) {
            $options = (new Options([
                'name' => $collectionId,
                'fields' => [],
                'format' => [],
                'model' => Model::class,
            ]))->merge($this->default);


            foreach ($this->getResolvers() as $resolver) {
                $resolved = $resolver($collectionId);
                if (null !== $resolved) {
                    $options->merge($resolved);
                    break;
                }
            }

            $this->collections[$collectionSignature] = new Collection(
                $connection,
                $options['name'],
                $options['fields'],
                $options['format'],
                $options['model']
            );
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

        if (null !== $this->renderer) {
            $render = $this->renderer;
            return $render($template, $data);
        } else {
            return $this->defaultRender($template, $data);
        }
    }

    public function defaultRender($template, array $data = [])
    {
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
            'connections' => array_keys($this->connections),
            'use' => $this->useConnection,
        ];
    }

    public function getAttribute($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }
}
