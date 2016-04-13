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
    protected $compositions = [];

    protected $repository;
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
     * Norm Connection
     *
     * @var Norm\Connection
     */
    protected $connection;

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
     * Collection cache
     *
     * @var array
     */
    // protected $cache;

    protected $primaryKey = 'id';

    protected $modelClass;

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(Repository $repository, Connection $connection, $options = [])
    {
        if (!isset($options['name'])) {
            throw new NormException('Missing name, check collection configuration!');
        }

        $this->repository = $repository;
        $this->connection = $connection;

        $options = Options::create([
            'schema' => [],
            'model' => Model::class,
            'observers' => [],
        ])->merge($options);

        $this->name = Inflector::classify($options['name']);
        $this->id = isset($options['id']) ? $options['id'] : Inflector::tableize($this->name);
        $this->modelClass = $options['model'];

        foreach ($options['observers'] as $meta) {
            if (is_array($meta) && !isset($meta[0])) {
                $this->observe($meta);
            } else {
                $this->observe($this->resolve($meta));
            }
        }

        $this->schema = $this->resolve(Schema::class, [
            'collection' => $this,
            'fields' => $options['schema']
        ]); //new Schema($this, $options['schema']);

        if (isset($options['format'])) {
            $this->schema->addFormatter($options['format']);
        }
    }

    // public function withConnection(Connection $connection)
    // {
    //     $this->connection = $connection;

    //     return $this;
    // }

    /**
     * Getter of collection name. Collection name usually mapped to table name or
     * collection name
     *
     * @return string
     */
    // public function getName()
    // {
    //     return $this->name;
    // }

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

    // /**
    //  * Getter of connection
    //  *
    //  * @return Norm\Connection
    //  */
    // protected function getConnection()
    // {
    //     if (is_null($this->connection)) {
    //         throw new NormException('Connection not found');
    //     }
    //     return $this->connection;
    // }

    // /**
    //  * Getter of collection option
    //  *
    //  * @param string $key
    //  *
    //  * @return mixed
    //  */
    // public function option($key = null)
    // {
    //     if (func_num_args() ===  0) {
    //         return $this->options;
    //     } elseif (isset($this->options[$key])) {
    //         return $this->options[$key];
    //     }
    // }

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
        return $this->schema;
    }

    // public function withSchema($schema)
    // {
    //     $this->schema = new Schema($this, $schema);

    //     return $this;
    // }

    public function getFilter()
    {
        if (is_null($this->filter)) {
            $this->filter = new Filter($this, $this->schema->getFilterRules());
        }

        return $this->filter;
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
        if (!is_null($criteria) && !is_array($criteria)) {
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
    public function save(Model $model, $options = [])
    {
        $options = array_merge([
            'filter' => true,
            'observer' => true,
        ], $options);

        if ($options['filter']) {
            $this->filter($model);
        }

        $save = function ($context) {
            $context['modified'] = $this->connection->persist($this->getId(), $context['model']->dump());
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
    public function remove(Model $model = null)
    {
        if (func_num_args() === 0) {
            $this->connection->remove($this);
        } else {
            // avoid remove empty model
            if (is_null($model)) {
                throw new NormException('Cannot remove null model');
            }

            $context = new UtilCollection([
                'collection' => $this,
                'model' => $model,
            ]);

            $this->apply('remove', $context, function ($context) {
                $result = $this->connection->remove($this->getId(), $context['model']['$id']);
                if ($result) {
                    $context['model']->reset();
                }
            });
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
        $methods = [ 'save', 'filter', 'remove', 'search', 'attach', 'initialize', ];
        if (is_array($observer)) {
            foreach ($methods as $method) {
                if (isset($observer[$method]) && is_callable($observer[$method])) {
                    $this->compose($method, $observer[$method]);
                }
            }
        } elseif (is_object($observer)) {
            foreach ($methods as $method) {
                if (method_exists($observer, $method)) {
                    $this->compose($method, [$observer, $method]);
                }
            }
        } else {
            throw new NormException('Observer must be array or object');
        }

        $context = new UtilCollection([
            'collection' => $this,
        ]);
        $this->apply('initialize', $context);
    }

    /**
     * Json serialization of this id.
     *
     * @method jsonSerialize
     *
     * @return string
     */
    // public function jsonSerialize()
    // {
    //     return $this->id;
    // }

    public function __call($method, $args)
    {
        switch ($method) {
            case 'cursorDistinct':
            case 'cursorFetch':
            case 'cursorSize':
            case 'cursorRead':
                return call_user_func_array([$this->connection, $method], $args);
            default:
                throw new NormException('Collection does not have method ' . $method);
        }
    }

    public function cursorRead($context, $position = 0)
    {
        $row = $this->connection->cursorRead($context, $position);
        return is_null($row) ? $row : $this->attach($row);
    }

    public function factory($collectionId = '', $connectionId = '')
    {
        return $this->repository->factory($collectionId, $connectionId ?: $this->connection->getId());
    }

    public function __debugInfo()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connectionClass' => $this->connection ? get_class($this->connection) : null,
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
