<?php namespace Norm;

use Norm\Model;
use Norm\Cursor;
use Norm\Type\Object;
use Norm\Filter\Filter;
use ROH\Util\Inflector;
use JsonKit\JsonSerializer;

/**
 * The collection class that wraps models.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Collection extends Hookable implements JsonSerializer
{
    /**
     * Logical class name
     *
     * @var string
     */
    protected $clazz;

    /**
     * Data source representative name
     *
     * @var string
     */
    protected $name;

    /**
     * Norm Connection
     *
     * @var Norm\Connection
     */
    protected $connection;

    /**
     * Schema
     *
     * @var Norm\Type\Object
     */
    protected $schema;

    /**
     * Collection options
     *
     * @var array
     */
    protected $options;

    /**
     * Collection data filters
     *
     * @var array
     */
    protected $filter;

    /**
     * Collection cache
     *
     * @var array
     */
    protected $cache;

    /**
     * Constructor
     *
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

        if (isset($options['schema'])) {
            $this->schema = new Object($options['schema']);
            unset($options['schema']);
        } else {
            $this->schema = new Object();
        }

        $this->options = $options;

        $this->applyHook('initialized', $this);

        $this->resetCache();
    }

    /**
     * Getter of collection name. Collection name usually mapped to table name or collection name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Getter of collection class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->clazz;
    }

    /**
     * Getter of connection
     *
     * @return Norm\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Getter of collection option
     *
     * @param string $key
     *
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
     * Getter and setter of collection schema. If there is no argument specified, the method will set and override schema. If argument specified, method will act as getter to specific field schema.
     *
     * @param string $schema
     *
     * @return mixed
     */
    public function schema($key = null, $value = null)
    {
        $numArgs = func_num_args();
        if (0 === $numArgs) {
            return $this->schema;
        } elseif (1 === $numArgs) {
            if (is_array($key)) {
                $this->schema = new Object($key);
            } elseif ($this->schema->offsetExists($key)) {
                return $this->schema[$key];
            }
        } else {
            $this->schema[$key] = $value;
        }
    }

    /**
     * Prepare data value for specific field name
     *
     * @param  string             $key    Field name
     * @param  mixed              $value  Original data value
     * @param  Norm\Schema\Field  $schema If specified will override default schema
     *
     * @return mixed Prepared data value
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
     *
     * @param mixed document Raw document data
     *
     * @return Norm\Model Attached model
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
     *
     * @param array $criteria
     *
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
     *
     * @param  array|mixed $criteria   Criteria to search
     *
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
     *
     * @param array|\Norm\Model $cloned Model to clone
     *
     * @return \Norm\Model
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
     * Filter model data with functions to cleanse, prepare and validate data. When key argument specified, filter will run partially for specified key only.
     *
     * @param \Norm\Model   $model
     *
     * @param string $key Key field of model
     *
     * @return bool True if success and false if fail
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
     *
     * @param \Norm\Model $model
     * @param array       $options
     *
     * @return void
     */
    public function save(Model $model, $options = array())
    {
        $options = array_merge(array(
            'filter' => true,
            'observer' => true,
        ), $options);

        if ($options['filter']) {
            $this->filter($model);
        }

        if ($options['observer']) {
            $this->applyHook('saving', $model, $options);
        }

        $modified = $this->connection->persist($this, $model->dump());
        $model->setId($modified['$id']);

        if ($options['observer']) {
            $this->applyHook('saved', $model, $options);
        }

        $model->sync($modified);
        $this->resetCache();
    }

    /**
     * Remove single model
     *
     * @param \Norm\Model $model
     *
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
     * Override this to add new functionality of observer to the collection, otherwise you are not necessarilly to know about this.
     * @param object $observer
     *
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

        if (method_exists($observer, 'initialized')) {
            $this->hook('initialized', array($observer, 'initialized'));
        }
    }

    /**
     * Reset Cache
     *
     * @method resetCache
     *
     * @return void
     */
    protected function resetCache()
    {
        $this->cache = array();
    }

    /**
     * Put item in cache bags.
     *
     * @method rememberCache
     *
     * @param mixed       $criteria
     * @param \Norm\Model $model    [description]
     *
     * @return void
     */
    protected function rememberCache($criteria, $model)
    {
        $ser = serialize($criteria);

        $this->cache[$ser] = $model;
    }

    /**
     * Get item from cache.
     *
     * @method fetchCache
     *
     * @param object $criteria
     *
     * @return void|\Norm\Model
     */
    protected function fetchCache($criteria)
    {
        $ser = serialize($criteria);

        if (isset($this->cache[$ser])) {
            return $this->cache[$ser];
        }
    }

    /**
     * Json serialization of this class.
     *
     * @method jsonSerialize
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->clazz;
    }
}
