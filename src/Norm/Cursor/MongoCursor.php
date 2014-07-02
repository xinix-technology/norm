<?php

namespace Norm\Cursor;

class MongoCursor implements ICursor
{

    protected $collection;

    protected $criteria;

    protected $sort;

    protected $skip;

    protected $limit;

    protected $cursor;

    public function __construct($collection = null)
    {
        $this->collection = $collection;

        $this->criteria = $this->prepareCriteria($collection->criteria);

        // var_dump('criteria', $this->criteria);
    }

    public function getCursor()
    {
        if (is_null($this->cursor)) {
            $rawCollection = $this->collection->connection->getRaw()->{$this->collection->name};

            if (isset($this->criteria)) {
                $this->cursor = $rawCollection->find($this->criteria);
            } else {
                $this->cursor = $rawCollection->find();
            }
            if (isset($this->sort)) {
                $this->cursor->sort($this->sort);
            }
            if (isset($this->skip)) {
                $this->cursor->skip($this->skip);
            }

            if (isset($this->limit)) {
                $this->cursor->limit($this->limit);
            }
        }

        return $this->cursor;
    }

    public function grammarExpression($key, $value)
    {
        if ($key === '!or' || $key === '!and') {
            $newValue = array();
            foreach ($value as $v) {
                $newValue[] = $this->prepareCriteria($v, true);
            }

            return array('$'.substr($key, 1), $newValue);
        }

        $splitted = explode('!', $key, 2);

        $field = $splitted[0];

        $schema = $this->collection->schema($field);

        if (strlen($field) > 0 && $field[0] === '$') {
            $field = '_'.substr($field, 1);
        }

        $operator = '$eq';
        $multiValue = false;
        if (isset($splitted[1])) {
            switch ($splitted[1]) {
                case 'like':
                    return array($field, array('$regex', new \MongoRegex("/$value/i")));
                case 'regex':
                    return array($field, array('$regex', new \MongoRegex($value)));
                case 'in':
                case 'nin':
                    $operator = '$'.$splitted[1];
                    $multiValue = true;
                    break;
                default:
                    $operator = '$'.$splitted[1];
                    break;
            }
        }

        if ($field === '_id') {
            if ($operator === '$eq') {
                return array($field, new \MongoId($value));
            } else {
                return array($field, array($operator => new \MongoId($value)));
            }
        }

        if (isset($schema)) {
            if ($multiValue) {
                if (!empty($value)) {
                    $newValue = array();
                    foreach ($value as $k => $v) {
                        // FIXME ini quickfix buat query norm array seperti mongo
                        if (!$schema instanceof \Norm\Schema\NormArray) {
                            $newValue[] = $schema->prepare($v);
                        }
                    }
                    $value = $newValue;
                } else {
                    $value = array();
                }
            } else {
                // FIXME ini quickfix buat query norm array seperti mongo
                if (!$schema instanceof \Norm\Schema\NormArray) {
                    $value = $schema->prepare($value);
                }
            }

        }
        $value = $this->collection->connection->marshall($value);

        if ($operator === '$eq') {
            return array($field, $value);
        } else {
            return array($field, array($operator => $value));
        }
    }

    public function prepareCriteria($criteria)
    {
        if (empty($criteria)) {
            return null;
        }

        $newCriteria = array();

        foreach ($criteria as $key => $value) {
            list($newKey, $newValue) = $this->grammarExpression($key, $value);

            if (is_array($newValue)) {
                if (!isset($newCriteria[$newKey])) {
                    $newCriteria[$newKey] = array();
                }
                $newCriteria[$newKey] = array_merge($newCriteria[$newKey], $newValue);
            } else {
                $newCriteria[$newKey] = $newValue;
            }

        }

        return $newCriteria;
    }

    public function getNext()
    {
        return $this->getCursor()->getNext();
    }

    public function current()
    {
        return $this->getCursor()->current();
    }

    public function next()
    {
        $this->getCursor()->next();
    }

    public function key()
    {
        return $this->getCursor()->key();
    }

    public function valid()
    {
        return $this->getCursor()->valid();
    }

    public function rewind()
    {
        $this->getCursor()->rewind();
    }

    public function limit($num = null)
    {
        if (func_num_args() === 0) {
            return $this->limit;
        }
        $this->limit = (int) $num;
        return $this;
    }

    public function sort(array $fields = array())
    {
        if (func_num_args() === 0) {
            return $this->sort;
        }
        $this->sort = $fields;
        return $this;
    }

    public function count($foundOnly = false)
    {
        return $this->getCursor()->count($foundOnly);
    }

    public function match($q)
    {
        if (is_null($q)) {
            return $this;
        }

        $orCriteria = array();

        $schema = $this->collection->schema();
        foreach ($schema as $key => $value) {
            $orCriteria[] = array($key => array('$regex' => new \MongoRegex("/$q/i")));
        }
        $this->criteria = array('$or' => $orCriteria);

        return $this;
    }

    public function skip($num = null)
    {
        if (func_num_args() === 0) {
            return $this->skip;
        }
        $this->skip = (int) $num;
        return $this;
    }

    public function getQueryInfo()
    {
        return $this->getCursor()->info();
    }
}
