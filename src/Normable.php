<?php

namespace Norm;

use Norm\Exception\NormException;
use ROH\Util\Collection as UtilCollection;

abstract class Normable extends UtilCollection
{
    protected $repository;

    function __construct(Repository $repository, array $attributes = [])
    {
        if (!($repository instanceof Repository)) {
            throw new NormException(get_called_class().': Undefined repository');
        }

        $this->repository = $repository;

        parent::__construct($attributes);
    }

    public function getAttribute($key)
    {
        return $this->repository->getAttribute($key);
    }

    public function translate($message)
    {
        return call_user_func_array([$this->repository, 'translate'], func_get_args());
    }

    public function render($template, array $data = [])
    {
        return $this->repository->render($template, $data);
    }

    public function resolve($contract, array $args = [])
    {
        return $this->repository->resolve($contract, $args);
    }

    abstract public function factory();
}