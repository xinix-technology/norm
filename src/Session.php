<?php

namespace Norm;

use Norm\Exception\NormException;

class Session
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $connections = [];

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function close()
    {
        $this->commit();
    }

    public function dispose()
    {
        $this->rollback();
        foreach ($this->connections as $name => $connection) {
            $this->manager->getPool($name)->release($connection);
        }
        $this->connections = [];
    }

    public function commit()
    {
        foreach ($this->connections as $name => $connection) {
            $connection->commit();
        }
    }

    public function rollback()
    {
        foreach ($this->connections as $name => $connection) {
            $connection->rollback();
        }
    }

    /**
     * Create new query
     *
     * @param mixed $schema
     * @param array $criteria
     * @return Query
     */
    public function factory($schema, $criteria = [])
    {
        return new Query($this, $schema, $criteria);
    }

    public function acquire(string $name)
    {
        $pool = $this->manager->getPool($name);
        $name = $pool->getName();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $pool->acquire();
            $this->connections[$name]->begin();
        }

        return $this->connections[$name];
    }

    public function parseSchema($name)
    {
        if (is_array($name)) {
            if (count($name) < 2) {
                throw new NormException('Malformed schema name tupple');
            }
            [ $connection, $schema ] = $name;
        } elseif (strpos($name, '.') !== false) {
            [ $connection, $schema ] = explode('.', $name);
        } else {
            $connection = $this->manager->getPool()->getName();
            $schema = $name;
        }

        $pool = $this->manager->getPool($connection);
        return [ $pool->getName(), $pool->getSchema($schema) ];
    }
}
