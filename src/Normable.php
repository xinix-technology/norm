<?php

namespace Norm;

use Norm\Exception\NormException;

abstract class Normable
{
    /**
     * [$repository description]
     * @var Normable
     */
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * [getAttribute description]
     * @param  string $key [description]
     * @return mixed       [description]
     */
    public function getAttribute($key)
    {
        return null === $this->repository ? null : $this->repository->getAttribute($key);
    }

    /**
     * [translate description]
     * @param  string $message [description]
     * @return string          [description]
     */
    public function translate($message)
    {
        return null === $this->repository
            ? $message
            : call_user_func_array([$this->repository, 'translate'], func_get_args());
    }

    /**
     * [render description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @return string           [description]
     */
    public function render($template, array $data = [])
    {
        return null === $this->repository ? null : $this->repository->render($template, $data);
    }

    /**
     * [factory description]
     * @return Collection [description]
     */
    public function factory($collectionId, $connectionId = '')
    {
        return null === $this->repository ? null : $this->repository->factory($collectionId, $connectionId);
    }
}