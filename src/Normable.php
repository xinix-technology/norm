<?php

namespace Norm;

use Norm\Exception\NormException;
use ROH\Util\Collection as UtilCollection;

abstract class Normable extends UtilCollection
{
    /**
     * [$repository description]
     * @var Repository
     */
    protected $repository;

    /**
     * [__construct description]
     * @param Repository $repository [description]
     * @param array      $attributes [description]
     */
    public function __construct(Repository $repository, array $attributes = [])
    {
        $this->repository = $repository;

        parent::__construct($attributes);
    }

    /**
     * [getAttribute description]
     * @param  string $key [description]
     * @return mixed       [description]
     */
    public function getAttribute($key)
    {
        return $this->repository->getAttribute($key);
    }

    /**
     * [translate description]
     * @param  string $message [description]
     * @return string          [description]
     */
    public function translate($message)
    {
        return call_user_func_array([$this->repository, 'translate'], func_get_args());
    }

    /**
     * [render description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @return string           [description]
     */
    public function render($template, array $data = [])
    {
        return $this->repository->render($template, $data);
    }

    /**
     * [resolve description]
     * @param  string $contract [description]
     * @param  array  $args     [description]
     * @return mixed            [description]
     */
    public function resolve($contract, array $args = [])
    {
        return $this->repository->resolve($contract, $args);
    }

    /**
     * [factory description]
     * @return Collection [description]
     */
    abstract public function factory();
}