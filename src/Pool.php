<?php

namespace Norm;

use ROH\Util\Injector;
use Norm\Exception\NormException;

class Pool
{
    protected static $id = 0;

    /**
     * @var Injector
     */
    protected $injector;

    /**
     * @var string
     */
    protected $name;

    protected $handler;

    /**
     * @var array
     */
    protected $schemas = [];

    protected $connections = [];

    public function __construct(Injector $injector, array $options)
    {
        $this->injector = $injector;
        $this->name = isset($options['name']) ? $options['name'] : 'pool-' . static::$id++;
        $this->handler = $options['handler'];

        if (isset($options['schemas'])) {
            foreach ($options['schemas'] as $schema) {
                $this->putSchema($schema);
            }
        }
    }

    public function putSchema(array $schemaDef)
    {
        if (empty($schemaDef['name'])) {
            throw new NormException('Schema name is required');
        }

        $fields = [];
        if (isset($schemaDef['fields'])) {
            foreach ($schemaDef['fields'] as $field) {
                $fields[] = $this->injector->resolve($field);
            }
        }

        $this->schemas[$schemaDef['name']] = new Schema(
            $schemaDef['name'],
            $fields,
            @$schemaDef['observers'] ?: [],
            @$schemaDef['modelClass'] ?: ''
        );
        return $this;
    }

    public function getSchema(string $name)
    {
        if (!isset($this->schemas[$name])) {
            $this->putSchema([ 'name' => $name ]);
        }

        return $this->schemas[$name];
    }

    public function getName()
    {
        return $this->name;
    }

    public function acquire()
    {
        if (count($this->connections) === 0) {
            $connection = $this->injector->resolve($this->handler);
            array_push($this->connections, $connection);
        }

        return array_shift($this->connections);
    }

    public function release(Connection $connection)
    {
        array_push($this->connections, $connection);
    }
}
