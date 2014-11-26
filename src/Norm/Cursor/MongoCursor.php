<?php

namespace Norm\Cursor;

use MongoId;
use Norm\Cursor;

class MongoCursor extends Cursor
{
    protected $cursor;

    /**
     * @see Norm\Cursor::getNext()
     */
    public function getNext()
    {
        $next = $this->getCursor()->getNext();
        return isset($next) ? $this->collection->attach($next) : null;
    }

    /**
     * @see  Norm\Cursor::count()
     */
    public function count($foundOnly = false)
    {
        return $this->getCursor()->count($foundOnly);
    }

    /**
     * @see  Norm\Cursor::translateCriteria()
     */
    public function translateCriteria(array $criteria = array())
    {
        if (empty($criteria)) {
            return $criteria;
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

    /**
     * @see  Norm\Cursor::current()
     */
    public function current()
    {
        $data = $this->getCursor()->current();
        return isset($data) ? $this->collection->attach($data) : null;
    }

    /**
     * @see  Norm\Cursor::next()
     */
    public function next()
    {
        $this->getCursor()->next();
    }

    /**
     * @see  Norm\Cursor::key()
     */
    public function key()
    {
        return $this->getCursor()->key();
    }

    /**
     * @see  Norm\Cursor::valid()
     */
    public function valid()
    {
        return $this->getCursor()->valid();
    }

    /**
     * @see  Norm\Cursor::rewind()
     */
    public function rewind()
    {
        $this->getCursor()->rewind();
    }

    public function getCursor()
    {
        if (is_null($this->cursor)) {
            $rawCollection = $this->connection->getRaw()->{$this->collection->getName()};

            if (empty($this->criteria)) {
                $this->cursor = $rawCollection->find();
            } else {
                $this->cursor = $rawCollection->find($this->criteria);
            }
            if (isset($this->sorts)) {
                $this->cursor->sort($this->sorts);
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
            if (!is_array($value)) {
                throw new \Exception('[Norm/MongoCursor] "!or" and "!and" must have value as array.');
            }

            $newValue = array();
            foreach ($value as $v) {
                $newValue[] = $this->translateCriteria($v, true);
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
                    return array($field, array('$regex' => new \MongoRegex("/$value/i")));
                case 'regex':
                    return array($field, array('$regex' => new \MongoRegex($value)));
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
                return array($field, new MongoId($value));
            } else {
                return array($field, array($operator => new MongoId($value)));
            }
        }

        if (isset($schema)) {
            if ($multiValue) {
                if (!empty($value)) {
                    $newValue = array();
                    foreach ($value as $k => $v) {
                        // TODO ini quickfix buat query norm array seperti mongo
                        // kalau ada yang lebih bagus caranya bisa dibenerin
                        if (!$schema instanceof \Norm\Schema\NormArray) {
                            $newValue[] = $schema->prepare($v);
                        }
                    }
                    $value = $newValue;
                } else {
                    $value = array();
                }
            } else {
                // TODO ini quickfix buat query norm array seperti mongo
                // kalau ada yang lebih bagus caranya bisa dibenerin
                if (!$schema instanceof \Norm\Schema\NormArray) {
                    $value = $schema->prepare($value);
                }
            }

        }
        $value = $this->connection->marshall($value);

        if ($operator === '$eq') {
            return array($field, $value);
        } else {
            return array($field, array($operator => $value));
        }
    }

    // implemented later
    public function distinct($key) {
        throw new \Exception('Unimplemented yet!');
    }
}
