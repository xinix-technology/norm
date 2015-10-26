<?php
namespace Norm;

// use JsonKit\JsonSerializer;
use Exception;
use InvalidArgumentException;
use Norm\Model;
use Norm\Cursor;
use Norm\Schema;
use Norm\Filter;
use ROH\Util\Options;
use ROH\Util\Thing;
use ROH\Util\Inflector;
use ROH\Util\Collection as UtilCollection;

/**
 * The collection class that wraps models.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2015 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Collection extends Base
// remove implementation of jsonserialize, if we need add again
// implements JsonSerializer
{
    protected $norm;
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
    public function __construct($norm, $options = array())
    {
        if (isset($norm) && !($norm instanceof Norm)) {
            throw new InvalidArgumentException('First arguments must be instance of Norm\Norm');
        }

        if (!isset($options['name'])) {
            throw new InvalidArgumentException('Missing name, check collection configuration!');
        }

        $this->norm = $norm;

        $options = Options::create([
            'schema' => [],
            'model' => Model::class,
            'observers' => [],
        ])->merge($options);

        $this->name = Inflector::classify($options['name']);
        $this->id = isset($options['id']) ? $options['id'] : Inflector::tableize($this->name);
        $this->modelClass = $options['model'];

        foreach ($options['observers'] as $observer) {
            $this->observe((new Thing($observer))->getHandler());
        }

        $this->schema = new Schema($this, $options['schema']);
        if (isset($options['format'])) {
            $this->schema->withFormatter($options['format']);
        }


        // $this->resetCache();
    }

    public function withConnection(Connection $connection)
    {
        $clone = clone $this;
        $clone->connection = $connection;

        $context = new UtilCollection([
            'collection' => $clone,
        ]);
        $this->apply('initialize', $context);

        return $clone;
    }

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

    /**
     * Getter of connection
     *
     * @return Norm\Connection
     */
    protected function getConnection()
    {
        if (is_null($this->connection)) {
            throw new Exception('Connection not found');
        }
        return $this->connection;
    }

    /**
     * Getter of collection option
     *
     * @param string $key
     *
     * @return mixed
     */
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

    public function withSchema($schema)
    {
        $this->schema = new Schema($this, $schema);

        return $this;
    }

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
    public function find($criteria = array())
    {
        if (!is_null($criteria) && !is_array($criteria)) {
            $criteria = array(
                '$id' => $criteria,
            );
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
    public function findOne($criteria = array())
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
            $context['modified'] = $this->getConnection()->persist($this->getId(), $context['model']->dump());
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
            $this->getConnection()->remove($this);
        } else {
            // avoid remove empty model
            if (is_null($model)) {
                throw new Exception('Cannot remove null model');
            }

            $context = new UtilCollection([
                'collection' => $this,
                'model' => $model,
            ]);

            $this->apply('remove', $context, function ($context) {
                $result = $this->getConnection()->remove($this->getId(), $context['model']['$id']);
                if ($result) {
                    $context['model']->reset();
                }
            });
        }
    }

    /**
     * Override this to add new functionality of observer to the collection,
     * otherwise you are not necessarilly to know about this.
     * @param object $observer
     *
     * @return void
     */
    public function observe($observer)
    {

        if (method_exists($observer, 'save')) {
            $this->compose('save', array($observer, 'save'));
        }

        if (method_exists($observer, 'filter')) {
            $this->compose('filter', array($observer, 'filter'));
        }

        if (method_exists($observer, 'remove')) {
            $this->compose('remove', array($observer, 'remove'));
        }

        if (method_exists($observer, 'search')) {
            $this->compose('search', array($observer, 'search'));
        }

        if (method_exists($observer, 'attach')) {
            $this->compose('attach', array($observer, 'attach'));
        }

        if (method_exists($observer, 'initialize')) {
            $this->compose('initialize', array($observer, 'initialize'));
        }
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
        $connectionMethods = [
            'cursorDistinct', 'cursorFetch', 'cursorSize', 'cursorRead'
        ];
        $normMethods = [
            'translate',
            'render'
        ];
        if (in_array($method, $connectionMethods)) {
            return call_user_func_array([$this->getConnection(), $method], $args);
        } elseif (in_array($method, $normMethods)) {
            return call_user_func_array([$this->norm, $method], $args);
        }

        throw new \Exception('Undefined method or method handler: '. $method);
    }

    public function cursorRead($context, $position = 0)
    {
        $row = $this->getConnection()->cursorRead($context, $position);
        return is_null($row) ? $row : $this->attach($row);
    }

    public function factory($collectionId = null, $connectionId = null)
    {
        if (is_null($collectionId)) {
            return $this;
        }
        return $this->norm->factory($collectionId, $connectionId ?: $this->getConnection()->getId());
    }

    public function __debugInfo()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connectionClass' => $this->connection ? get_class($this->connection) : null,
        ];
    }
}
