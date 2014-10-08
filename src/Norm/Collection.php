<?php

namespace Norm;

use ROH\Util\Inflector;
use Norm\Model;
use Norm\Cursor;
use Norm\Filter\Filter;
use Norm\Type\Object;
use \JsonKit\JsonSerializer;

class Collection extends Hookable implements JsonSerializer
{
    /**
     * Logical class name
     * @var string
     */
    protected $clazz;

    /**
     * Data source representative name
     * @var string
     */
    protected $name;

    /**
     * Norm Connection
     * @var Norm\Connection
     */
    protected $connection;

    /**
     * Collection options
     * @var array
     */
    protected $options;

    /**
     * Collection data filters
     * @var array
     */
    protected $filter;

    /**
     * Collection cache
     * @var array
     */
    protected $cache;

    /**
     * Constructor
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['name'])) {
            throw new \Exception('[Norm/Collection] Missing name, check collection configuration!');
        }

        $this->clazz = Inflector::classify($options['name']);
        $this->name = Inflector::tableize($this->clazz);

        if (isset($options['connection'])) {
            $this->connection = $options['connection'];
            unset($options['connection']);

            $options['debug'] = $this->connection->option('debug') ? true : false;
        }

        if (isset($options['observers'])) {
            foreach ($options['observers'] as $Observer => $observerOptions) {
                if (is_int($Observer)) {
                    $Observer = $observerOptions;
                    $observerOptions = null;
                }

                if (is_string($Observer)) {
                    $Observer = new $Observer($observerOptions);
                }
                $this->observe($Observer);
            }
        }

        $this->options = $options;

        $this->resetCache();
    }

    /**
     * Getter of collection name
     * Collection name usually mapped to table name or collection name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Getter of collection class
     * @return string
     */
    public function getClass()
    {
        return $this->clazz;
    }

    /**
     * Getter of connection
     * @return Norm\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Getter of collection option
     * @param  string $key
     * @return mixed
     */
    public function option($key = null)
    {
        if (func_num_args() ===  0) {
            return $this->options;
        } elseif (isset($this->options[$key])) {
            return $this->options[$key];
        }
    }

    /**
     * Getter and setter of collection schema
     * If there is no argument specified, the method will set and override schema
     * If argument specified, method will act as getter to specific field schema.
     *
     * @param  string $schema
     * @return mixed
     */
    public function schema($schema = null)
    {
        if (!isset($this->options['schema'])) {
            $this->options['schema'] = array();
        }

        if (func_num_args() === 0) {
            return $this->options['schema'];
        } elseif (is_array($schema)) {
            $this->options['schema'] = $schema;
        } elseif (empty($schema)) {
            $this->options['schema'] = array();
        } elseif (isset($this->options['schema'][$schema])) {
            return $this->options['schema'][$schema];
        }
    }

    /**
     * Prepare data value for specific field name
     * @param  string               $key    Field name
     * @param  mixed                $value  Original data value
     * @param  Norm\Schema\Field    $schema If specified will override default schema
     * @return mixed                        Prepared data value
     */
    public function prepare($key, $value, $schema = null)
    {
        if (is_null($schema)) {
            $schema = $this->schema($key);
            if (is_null($schema)) {
                return $value;
                // throw new \Exception('Cannot prepare data to set. Schema not found for key ['.$key.'].');
            }
        }
        return $schema->prepare($value);
    }

    /**
     * Attach document to Norm system as model.
     * @param  mixed    $document   Raw document data
     * @return Norm\Model           Attached model
     */
    public function attach($document)
    {
        if (isset($this->connection)) {
            $document = $this->connection->unmarshall($document);
        }

        // wrap document as object instance to make sure it can be override by hooks
        $document = new Object($document);

        $this->applyHook('attaching', $document);
        if (isset($this->options['model'])) {
            $Model = $this->options['model'];
            $model = new $Model($document->toArray(), array(
                'collection' => $this,
            ));
        } else {
            $model = new Model($document->toArray(), array(
                'collection' => $this,
            ));
        }
        $this->applyHook('attached', $model);

        return $model;
    }

    /**
     * Find data with specified criteria
     * @param  array        $criteria
     * @return Norm\Cursor
     */
    public function find($criteria = array())
    {
        if (!is_array($criteria)) {
            $criteria = array(
                '$id' => $criteria,
            );
        }

        // wrap criteria as object instance to make sure it can be override by hooks
        $criteria = new Object($criteria);

        $this->applyHook('searching', $criteria);
        $cursor = $this->connection->query($this, $criteria->toArray());
        $this->applyHook('searched', $cursor);

        return $cursor;
    }

    /**
     * Find one document from collection
     * @param  array|mixed        $criteria   Criteria to search
     * @return Norm\Model
     */
    public function findOne($criteria = array())
    {
        $model = $this->fetchCache($criteria);

        if (is_null($model)) {
            $cursor = $this->find($criteria);
            $model = $cursor->getNext();
            $this->rememberCache($criteria, $model);
        }

        return $model;
    }

    /**
     * Create new instance of model
     * @param   array|Norm\Model    $cloned Model to clone
     * @return  Norm\Model
     */
    public function newInstance($cloned = array())
    {
        if ($cloned instanceof Model) {
            $cloned = $cloned->toArray(Model::FETCH_PUBLISHED);
        }

        if (isset($this->options['model'])) {
            $Model = $this->options['model'];
            return new $Model($cloned, array('collection' => $this));
        } else {
            return new Model($cloned, array('collection' => $this));
        }
    }

    /**
     * Filter model data with functions to cleanse, prepare and validate data.
     * When key argument specified, filter will run partially for specified key only.
     * @param  Norm\Model   $model
     * @param  string       $key    Key field of model
     * @return bool                 True if success and false if fail
     */
    public function filter(Model $model, $key = null)
    {
        if (is_null($this->filter)) {
            $this->filter = Filter::fromSchema($this->schema());
        }


        $this->applyHook('filtering', $model, $key);
        $result = $this->filter->run($model, $key);
        $this->applyHook('filtered', $model, $key);

        return $result;
    }

    /**
     * Save model to persistent state
     * @param  Model  $model
     * @param  array  $options
     * @return void
     */
    public function save(Model $model, $options = array())
    {
        $options = array_merge(array( 'filter' => true ), $options);

        if ($options['filter']) {
            $this->filter($model);
        }

        $this->applyHook('saving', $model, $options);
        $modified = $this->connection->persist($this, $model->dump());
        $model->sync($modified);
        $this->resetCache();
        $this->applyHook('saved', $model, $options);
    }

    /**
     * Remove single model
     * @param  Norm\Model     $model
     * @return void
     */
    public function remove(Model $model = null)
    {
        if (func_num_args() === 0) {
            $this->connection->remove($this);
        } else {
            // avoid remove empty model
            if (is_null($model)) {
                throw new \Exception('[Norm/Collection] Cannot remove null model');
            }

            $this->applyHook('removing', $model);
            $result = $this->connection->remove($this, $model);
            if ($result) {
                $model->reset();
            }

            $this->applyHook('removed', $model);
        }
    }

    /**
     * Override this to add new functionality of observer to the collection, otherwise you are not
     * necessarilly to know about this.
     * @param  object   $observer
     * @return void
     */
    protected function observe($observer)
    {
        if (method_exists($observer, 'saving')) {
            $this->hook('saving', array($observer, 'saving'));
        }

        if (method_exists($observer, 'saved')) {
            $this->hook('saved', array($observer, 'saved'));
        }

        if (method_exists($observer, 'filtering')) {
            $this->hook('filtering', array($observer, 'filtering'));
        }

        if (method_exists($observer, 'filtered')) {
            $this->hook('filtered', array($observer, 'filtered'));
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

        if (method_exists($observer, 'attaching')) {
            $this->hook('attaching', array($observer, 'attaching'));
        }

        if (method_exists($observer, 'attached')) {
            $this->hook('attached', array($observer, 'attached'));
        }
    }

    protected function resetCache()
    {
        $this->cache = array();
    }

    protected function rememberCache($criteria, $model)
    {
        $ser = serialize($criteria);
        $this->cache[$ser] = $model;
    }

    protected function fetchCache($criteria)
    {
        $ser = serialize($criteria);
        if (isset($this->cache[$ser])) {
            return $this->cache[$ser];
        }
    }

    public function jsonSerialize()
    {
        return $this->clazz;
    }
}
