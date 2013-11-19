<?php

namespace Norm;

use Reekoheek\Util\Inflector;
use Norm\Model;
use Norm\Filter\Filter;

class Collection implements \JsonKit\JsonSerializer {
    public $clazz;
    public $name;
    public $connection;
    public $schema;

    public $criteria;

    protected $options;
    protected $filter;


    public function __construct(array $options = array()) {
        $this->options = $options;
        $this->clazz = Inflector::classify($options['name']);
        $this->name = Inflector::tableize($this->clazz);
        $this->connection = $options['connection'];
    }

    public function schema($schema = NULL) {
        if (is_null($schema)) {
            return $this->schema;
        } else {
            $this->schema = $schema;
        }
    }

    public function hydrate($cursor) {
        $results = array();
        foreach ($cursor as $key => $doc) {
            $doc = $this->connection->prepare($doc);
            if (isset($this->options['model'])) {
                $Model = $this->options['model'];
                $results[] = new $Model($doc, array(
                    'collection' => $this,
                ));
            } else {
                $results[] = new Model($doc, array(
                    'collection' => $this,
                ));
            }
        }
        return $results;
    }

    public function criteria($criteria = null) {
        if (isset($criteria)) {
            if (!isset($this->criteria)) {
                $this->criteria = array();
            }

            if (is_array($criteria)) {
                $this->criteria = $this->criteria + $criteria;
            } else {
                $this->criteria = array('$id' => $criteria);
            }
        }
    }

    public function find(array $criteria = null) {
        $this->criteria($criteria);
        $result = $this->hydrate($this->connection->query($this));
        $this->criteria = null;
        return $result;
    }

    public function findOne($criteria = null) {
        $this->criteria($criteria);

        $cursor = $this->connection->query($this);

        $result = null;

        if ($o = $cursor->getNext()) {
            $hydrate = $this->hydrate(array($o));
            $result = $hydrate[0];
        }

        $this->criteria = null;

        return $result;
    }

    public function newInstance($cloned = array()) {
        if ($cloned instanceof Model) {
            $cloned = $cloned->toArray(Model::FETCH_PUBLISHED);
        }
        if (isset($this->options['model'])) {
            $Model = $this->options['model'];
            return new $Model($cloned, array('collection' => $this));
        }
        return new Model($cloned, array('collection' => $this));
    }

    public function save(Model $model, $options = array()) {
        if (!isset($options['filter']) || $options['filter'] === true) {
            $this->filter($model);
        }

        $result = $this->connection->save($this, $model);

        $this->criteria = null;
        return $result;
    }

    public function filter(Model $model, $key = NULL) {
        if (is_null($this->filter)) {
            $this->filter = Filter::fromSchema($this->schema());
        }


        if (is_null($key)) {
            $this->filter->run($model);
            $errors = $this->filter->errors();
            // var_dump($errors);
            if ($errors) {
                throw (new \Norm\Filter\FilterException())->sub($errors);
            }
        } else {
            // $backtrace = debug_backtrace();
            // foreach ($backtrace as $trace) {
            //     var_dump($trace['file'].':'.$trace['line'].' -> '.$trace['class'].'::'.$trace['function']);
            // }
            throw new \Exception(__METHOD__.' unimplemented selective field filter.');
        }
    }

    public function remove($model) {
        $result = $this->connection->remove($this, $model);
        if ($result) {
            $model->reset();
        }
        $this->criteria = NULL;
        return $result;
    }

    public function migrate() {
        $this->connection->migrate($this);
    }

    public function jsonSerialize() {
        return $this->clazz;
    }

}
