<?php

namespace Norm;

use ROH\Util\Inflector;
use Norm\Model;
use Norm\Cursor;
use Norm\Filter\Filter;

class Collection extends Hookable implements \JsonKit\JsonSerializer {
    public $clazz;
    public $name;
    public $connection;
    // public $schema;
    public $options;

    public $criteria;

    protected $filter;


    public function __construct(array $options = array()) {
        $this->options = $options;

        $this->clazz = Inflector::classify($options['name']);
        $this->name = Inflector::tableize($this->clazz);
        $this->connection = $options['connection'];

        if (isset($options['observers'])) {
            foreach($options['observers'] as $observer => $options) {
                if (is_string($observer)) {
                    $observer = new $observer($options);
                }
                $this->observe($observer);
            }
        }
    }

    public function observe($observer) {
        if (method_exists($observer, 'saving')) {
            $this->hook('saving', array($observer, 'saving'));
        }

        if (method_exists($observer, 'saved')) {
            $this->hook('saved', array($observer, 'saved'));
        }

        if (method_exists($observer, 'removing')) {
            $this->hook('removing', array($observer, 'removing'));
        }

        if (method_exists($observer, 'removed')) {
            $this->hook('removed', array($observer, 'removed'));
        }

        if (method_exists($observer, 'searching')) {
            $this->hook('searching', array($observer, 'searching'));
        }

        if (method_exists($observer, 'searched')) {
            $this->hook('searched', array($observer, 'searched'));
        }
    }

    public function schema($schema = NULL) {
        if (!isset($this->options['schema'])) {
            $this->options['schema'] = array();
        }

        if (is_null($schema)) {
            return $this->options['schema'];
        } else {
            $this->options['schema'] = $schema;
        }
    }

    public function prepare($key, $value, $schema = NULL) {
        if (is_null($schema)) {
            $collectionSchema = $this->schema();

            if (!array_key_exists($key, $collectionSchema)) {
                return $value;
                // throw new \Exception('Cannot prepare data to set. Schema not found for key ['.$key.'].');
            }
            $schema = $collectionSchema[$key];
        }
        return $schema->prepare($value);
    }

    public function hydrate($cursor) {
        $results = array();
        foreach ($cursor as $key => $doc) {
            $results[] = $this->attach($doc);
        }
        return $results;
    }

    public function attach($doc) {
        $doc = $this->connection->prepare($this, $doc);
        if (isset($this->options['model'])) {
            $Model = $this->options['model'];
            return new $Model($doc, array(
                'collection' => $this,
            ));
        } else {
            return new Model($doc, array(
                'collection' => $this,
            ));
        }
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

    public function find($criteria = null) {
        $this->criteria($criteria);

        $this->applyHook('searching', $this);

        $result = $this->connection->query($this);

        $this->applyHook('searched', $this, $result);

        $cursor = new Cursor($result, $this);

        $this->criteria = null;

        return $cursor;
    }

    public function findOne($criteria = null) {
        $cursor = $this->find($criteria);
        $this->criteria = null;
        return $cursor->getNext();
    }

    // DEPRECATED reekoheek: moved to observer
    // public function rebuildTree($parent, $left) {
    //     // the right value of this node is the left value + 1
    //     $right = $left+1;

    //     // get all children of this node
    //     // $result = mysql_query('SELECT title FROM tree '.
    //     //                        'WHERE parent="'.$parent.'";');

    //     $result = $this->find(array('parent' => $parent));

    //     // while ($row = mysql_fetch_array($result)) {

    //     foreach ($result as $row) {
    //         // recursive execution of this function for each
    //         // child of this node
    //         // $right is the current right value, which is
    //         // incremented by the rebuild_tree function
    //         $right = $this->rebuildTree($row['$id'], $right);
    //     }

    //     // we've got the left value, and now that we've processed
    //     // the children of this node we also know the right value
    //     // mysql_query('UPDATE tree SET lft='.$left.', rgt='.
    //     //              $right.' WHERE title="'.$parent.'";');
    //     if (isset($parent)) {
    //         $model = $this->findOne($parent);
    //         $model['$lft'] = $left;
    //         $model['$rgt'] = $right;
    //         $model->save();
    //     }

    //     // return the right value of this node + 1
    //     return $right+1;
    // }

    // DEPRECATED reekoheek
    // public function findTree($parent, $criteria = null) {
    //     $this->criteria($criteria);

    //     if (empty($parent)) {
    //         $cursor = $this->connection->query($this)->sort(array('_lft' => 1));

    //         $right = array();
    //         $cache = array();

    //         $result = array();
    //         foreach ($cursor as $row) {
    //             if (count($right)>0) {
    //                 while (!empty($right[count($right)-1]) && $right[count($right)-1] < $row['_rgt']) {
    //                     array_pop($right);
    //                 }
    //             }

    //             $model = $this->attach($row);

    //             $cache[$row['_rgt']] = $model;

    //             if (count($right) > 0) {
    //                 $cache[$right[count($right)-1]]->add('children', $model);
    //             } else {
    //                 $result[$row['_rgt']] = &$cache[$row['_rgt']];
    //             }

    //             $right[] = $row['_rgt'];
    //         }

    //         return $result;

    //     } else {
    //         // FIXME reekoheek: unimplemented yet!
    //         // $this->find(array('$id' => $parent))

    //     }
    // }

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

        $this->applyHook('saving', $model, $options);

        $result = $this->connection->save($this, $model);

        $this->applyHook('saved', $model, $options);

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

            if ($errors) {
                $err = new \Norm\Filter\FilterException();
                throw $err->sub($errors);
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

        $this->applyHook('removing', $model);

        $result = $this->connection->remove($this, $model);
        if ($result) {
            $model->reset();
        }

        $this->applyHook('removed', $model);

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
