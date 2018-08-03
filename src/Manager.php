<?php

namespace Norm;

use Exception;
use Norm\Exception\NormException;
use ROH\Util\Injector;

class Manager
{
    /**
     * @var Injector
     */
    protected $injector;

    /**
     * Main pool name
     *
     * @var string
     */
    protected $main = '';

    /**
     * @var array
     */
    protected $pools = [];

    public function __construct(Injector $injector, array $connections = [])
    {
        $this->injector = $injector;

        foreach ($connections as $connectionDef) {
            $this->putPool($connectionDef);
        }
    }

    public function putPool($connectionDef)
    {
        $pool = new Pool($this->injector, $connectionDef);
        $poolName = $pool->getName();

        $this->pools[$poolName] = $pool;

        if (isset($connectionDef['main']) && $connectionDef['main']) {
            $this->main = $poolName;
        }

        if ($this->main === '') {
            $this->main = $poolName;
        }

        return $this;
    }

    public function runSession(callable $fn, $options = [])
    {
        $session = $this->openSession($options);
        try {
            $result = $fn($session);

            $session->close();
            $session->dispose();

            return $result;
        } catch (Exception $err) {
            $session->dispose();
            throw $err;
        }
    }

    public function openSession($options = [])
    {
        return new Session($this);
    }

    /**
     * Get pool by its name
     *
     * @param string $name
     * @return Pool
     */
    public function getPool(string $name = '')
    {
        if ($name === '' && $this->main === '') {
            throw new NormException('No main connection found');
        }

        $name = $name ?: $this->main;
        $pool = $this->pools[$name];
        if (!$pool) {
            throw new NormException('Pool ' . $name . ' not found');
        }

        return $pool;
    }
}
