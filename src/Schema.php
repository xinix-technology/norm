<?php

namespace Norm;

use Norm\Exception\NormException;
use Norm\Exception\FilterException;
use ROH\Util\Composition;

class Schema
{
    /**
     * @var Composition
     */
    protected $observerRunner;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $observers;

    /**
     * @var string
     */
    protected $modelClass;

    public function __construct(
        string $name,
        array $fields = [],
        array $observers = [],
        string $modelClass = ''
    ) {
        $this->name = $name;
        $this->fields = $fields;
        $this->observers = $observers;
        $this->modelClass = $modelClass ?: Model::class;
    }

    /**
     * Getter name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function observe(Query $query, callable $next)
    {
        if ($this->observerRunner === null) {
            $this->observerRunner = new Composition();

            foreach ($this->observers as $observer) {
                $this->observerRunner->compose(function (Query $query, callable $next) use ($observer) {
                    $mode = $query->getMode();
                    if (!method_exists($observer, $mode)) {
                        return $next();
                    }

                    return $observer->$mode($query, $next);
                });
            }
        }

        $this->observerRunner->setCore($next)->apply($query);
    }

    public function filter(Model $row, Session $session, $partial = false)
    {
        $err = new FilterException();

        if (empty($row)) {
            $err->addChild(new NormException('Cannot filter empty row'));
            throw $err;
        }

        foreach ($this->fields as $field) {
            try {
                $field->execFilter($row, $session, $partial);
            } catch (\Exception $childErr) {
                $err->addChild($childErr);
            }
        }

        if ($err->hasChildren()) {
            throw $err;
        }
    }

    public function attach(array $row)
    {
        $Model = $this->modelClass;
        return new $Model($row);
    }
}
