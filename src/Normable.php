<?php

namespace Norm;

use Norm\Exception\NormException;
use ROH\Util\Collection as UtilCollection;

abstract class Normable
{
    /**
     * [$parent description]
     * @var Normable
     */
    protected $parent;

    /**
     * [__construct description]
     * @param Normable   $parent [description]
     * @param array      $attributes [description]
     */
    public function __construct(Normable $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * [getAttribute description]
     * @param  string $key [description]
     * @return mixed       [description]
     */
    public function getAttribute($key)
    {
        return $this->parent->getAttribute($key);
    }

    /**
     * [translate description]
     * @param  string $message [description]
     * @return string          [description]
     */
    public function translate($message)
    {
        return call_user_func_array([$this->parent, 'translate'], func_get_args());
    }

    /**
     * [render description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @return string           [description]
     */
    public function render($template, array $data = [])
    {
        return $this->parent->render($template, $data);
    }

    /**
     * [resolve description]
     * @param  string $contract [description]
     * @param  array  $args     [description]
     * @return mixed            [description]
     */
    public function resolve($contract, array $args = [])
    {
        return $this->parent->resolve($contract, $args);
    }

    /**
     * [factory description]
     * @return Collection [description]
     */
    public function factory($collectionId, $connectionId = '')
    {
        return $this->parent->factory($collectionId, $connectionId);
    }
}