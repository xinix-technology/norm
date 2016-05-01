<?php
namespace Norm;

use Norm\Exception\NormException;
use Norm\Model;
use Norm\Cursor;
use Norm\Schema;
use Norm\Filter;
use ROH\Util\Options;
use ROH\Util\Inflector;
use ROH\Util\Collection as UtilCollection;
use ROH\Util\Composition;

/**
 * The collection class that wraps models.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Collection extends Normable
{
    /**
     * [$compositions description]
     * @var array
     */
    protected $compositions = [];

    /**
     * Logical id
     *
     * @var string
     */
    protected $id;

    /**
     * Data source representative name
     *
     * @var string
     */
    protected $name;

    /**
     * Schema
     *
     * @var ROH\Util\Collection
     */
    protected $schema;

    /**
     * Collection options
     *
     * @var array
     */
    // protected $options;

    /**
     * Collection data filters
     *
     * @var array
     */
    protected $filter;

    /**
     * [$modelClass description]
     * @var string
     */
    protected $modelClass;

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(Connection $connection = null, $name = '', Schema $schema = null, $model = Model::class)
    {
        parent::__construct($connection);

        // $options = Options::create([
        //     'schema' => [],
        //     'model' => Model::class,
        //     'observers' => [],
        // ])->merge($options);

        if (is_string($name) && '' !== $name) {
            $this->name = Inflector::classify($name);
            $this->id = Inflector::tableize($this->name);
        } elseif (is_array($name)) {
            $this->name = $name[0];
            $this->id = $name[1];
        } else {
            throw new NormException('Collection name must be string');
        }

        $this->schema = $schema;
        $this->modelClass = $model;

        // foreach ($options['observers'] as $meta) {
        //     if (is_array($meta) && !isset($meta[0])) {
        //         $this->observe($meta);
        //     } else {
        //         $this->observe($this->resolve($meta));
        //     }
        // }

        // $this->schema = $this->resolve(Schema::class, [
        //     'collection' => $this,
        //     'fields' => $options['schema']
        // ]); //new Schema($this, $options['schema']);

        // if (isset($options['format'])) {
        //     $this->schema->addFormatter($options['format']);
        // }
    }

    /**
     * Getter of collection class
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Getter and setter of collection schema. If there is no argument specified,
     * the method will set and override schema. If argument specified, method will
     * act as getter to specific field schema.
     *
     * @param string $schema
     *
     * @return mixed
     */
    public function getSchema()
    {
        if (null === $this->schema) {
            $this->schema = new Schema($this);
        }
        return $this->schema;
    }

    public function getFilter()
    {
        if (is_null($this->filter)) {
            $this->filter = new Filter($this, $this->getSchema()->getFilterRules());
        }

        return $this->filter;
    }

    /**
     * Attach document to Norm system as model.
     *
     * @param array Raw document data
     *
     * @return Norm\Model Attached model
     */
    public function attach(array $document)
    {
        // document should already marshalled from connection

        // wrap document as object instance to make sure it can be override by hooks
        $context = new UtilCollection([
            'collection' => $this,
            'document' => $document,
        ]);

        $this->apply('attach', $context, function ($context) {
            $context['model'] = new $this->modelClass($this, $context['document']);
        });

        return $context['model'];
    }

    /**
     * Find data with specified criteria
     *
     * @param array $criteria
     *
     * @return Norm\Cursor
     */
    public function find($criteria = [])
    {
        if (null !== $criteria && !is_array($criteria)) {
            $criteria = [
                '$id' => $criteria,
            ];
        }

        // wrap criteria as object instance to make sure it can be override by hooks
        $context = new UtilCollection([
            'collection' => $this,
            'criteria' => $criteria ?: [],
        ]);

        $this->apply('search', $context, function ($context) {
            $context['cursor'] = new Cursor($this, $context['criteria']);
        });

        return $context['cursor'];
    }

    /**
     * Find one document from collection
     *
     * @param  array|mixed $criteria   Criteria to search
     *
     * @return Norm\Model
     */
    public function findOne($criteria = [])
    {
        return $this->find($criteria)
            ->limit(1)
            ->first();
    }

    /**
     * Create new instance of model
     *
     * @param array|Norm\Model $attributes Model to clone
     *
     * @return Norm\Model
     */
    public function newInstance(array $attributes = [])
    {
        return new $this->modelClass($this, $attributes);
    }

    /**
     * Filter model data with functions to cleanse, prepare and validate data.
     * When key argument specified, filter will run partially for specified key only.
     *
     * @param Norm\Model   $model
     *
     * @param string $key Key field of model
     *
     * @return bool True if success and false if fail
     */
    public function filter(Model $model, $key = null)
    {
        $context = new UtilCollection([
            'collection' => $this,
            'model' => $model,
            'key' => $key,
        ]);

        return $this->apply('filter', $context, function ($context) {
            return $this->getFilter()->run($context['model'], $context['key']);
        });
    }

    /**
     * Save model to persistent state
     *
     * @param Norm\Model $model
     * @param array       $options
     *
     * @return void
     */
    public function save(Model $model, array $options = [])
    {
        $options = array_merge([
            'filter' => true,
            'observer' => true,
        ], $options);

        if ($options['filter']) {
            $this->filter($model);
        }

        $save = function ($context) {
            $context['modified'] = $this->parent->persist($this->getId(), $context['model']->dump());
        };

        $context = new UtilCollection([
            'collection' => $this,
            'model' => $model,
        ]);

        if ($options['observer']) {
            $this->apply('save', $context, $save);
        } else {
            $save($context);
        }
        $context['model']->sync($context['modified']);
    }

    /**
     * Remove single model
     *
     * @param Norm\Model $model
     *
     * @return void
     */
    public function remove($modelOrCursor = null, array $options = [])
    {
        $options = array_merge([
            'observer' => true,
        ], $options);

        $context = new UtilCollection([
            'collection' => $this,
        ]);

        if (null === $modelOrCursor) {
            $cursor = $this->find();
        } elseif ($modelOrCursor instanceof Model) {
            $context['model'] = $modelOrCursor;
            $cursor = $this->find($modelOrCursor['$id']);
        } elseif ($modelOrCursor instanceof Cursor) {
            $cursor = $modelOrCursor;
        }
        $context['cursor'] = $cursor;

        $remove = function ($context) {
            $this->parent->remove($context['cursor']);
            if (null !== $context['model']) {
                $context['model']->reset(true);
            }
        };

        if ($options['observer']) {
            $this->apply('remove', $context, $remove);
        } else {
            $remove($context);
        }
    }

    /**
     * Override this to add new functionality of observer to the collection,
     * otherwise you are not necessarilly to know about this.
     * @param object|array $observer
     *
     * @return void
     */
    public function observe($observer)
    {
        $context = new UtilCollection([
            'collection' => $this,
        ]);

        $methods = [ 'save', 'filter', 'remove', 'search', 'attach', ]; //'initialize', ];
        if (is_array($observer)) {
            foreach ($methods as $method) {
                if (isset($observer[$method]) && is_callable($observer[$method])) {
                    $this->compose($method, $observer[$method]);
                }
            }

            if (isset($observer['initialize']) && is_callable($observer['initialize'])) {
                $observer['initialize']($context);
            }
        } elseif (is_object($observer)) {
            foreach ($methods as $method) {
                if (method_exists($observer, $method)) {
                    $this->compose($method, [$observer, $method]);
                }
            }

            if (method_exists($observer, 'initialize')) {
                $observer->initialize($context);
            }
        } else {
            throw new NormException('Observer must be array or object');
        }

        // $this->apply('initialize', $context);
    }

    public function distinct(Cursor $cursor, $key)
    {
        return $this->parent->distinct($cursor, $key);
    }

    // public function fetch(Cursor $cursor)
    // {
    //     return $this->parent->fetch($cursor);
    // }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        return $this->parent->size($cursor, $withLimitSkip);
    }

    public function read(Cursor $cursor)
    {
        $row = $this->parent->read($cursor);
        return null === $row ? $row : $this->attach($row);
    }

    public function __debugInfo()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connectionClass' => $this->parent ? get_class($this->parent) : null,
        ];
    }

    public function compose($key, $value)
    {
        $this->getComposition($key)
            ->compose($value);

        return $this;
    }

    public function getComposition($key)
    {
        if (!isset($this->compositions[$key])) {
            $this->compositions[$key] = new Composition();
        }

        return $this->compositions[$key];
    }

    public function apply($key, $context = null, $callback = null)
    {
        $composition = $this->getComposition($key);

        if (func_num_args() > 2) {
            $composition->setCore($callback);
        }

        return $composition->apply($context);
    }
}
