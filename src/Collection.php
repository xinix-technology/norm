<?php
namespace Norm;

use Norm\Exception\NormException;
use Norm\Schema\NUnknown;
use Norm\Schema\NField;
use ROH\Util\Options;
use ROH\Util\Inflector;
use ROH\Util\Collection as UtilCollection;
use ROH\Util\Composition;
use ROH\Util\Injector;
use ROH\Util\StringFormatter;
use Iterator;

/**
 * The collection class that wraps models.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Collection extends Normable implements Iterator
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
     * [$modelClass description]
     * @var string
     */
    protected $modelClass;

    /**
     * [$connection description]
     * @var Norm\Connection;
     */
    protected $connection;

    /**
     * [$firstField description]
     * @var [type]
     */
    protected $firstField;

    /**
     * [$fields description]
     * @var array
     */
    protected $fields = [];

    /**
     * [$formatters description]
     * @var array
     */
    protected $formatters = [];

    /**
     * Collection data filters
     *
     * @var array
     */
    protected $filter;

    /**
     * [__construct description]
     * @param Connection $connection [description]
     * @param [type]     $name       [description]
     * @param array      $fields     [description]
     * @param array      $format     [description]
     * @param [type]     $model      [description]
     */
    public function __construct(Connection $connection, $name, array $fields = [], array $format = [], $model = Model::class)
    {
        parent::__construct($connection->getRepository());

        foreach ($fields as $field) {
            $this->addField($field);
        }

        $this->formatters = array_merge([
            'plain' => [$this, 'formatPlain'],
            'tableFields' => [$this, 'formatTableFields'],
            'inputFields' => [$this, 'formatInputFields'],
        ], $format);

        // what is it for?
        // $this->filter = new Filter($this);

        $this->connection = $connection;

        if (is_string($name) && '' !== $name) {
            $this->name = Inflector::classify($name);
            $this->id = Inflector::tableize($this->name);
        } elseif (is_array($name)) {
            $this->name = $name[0];
            $this->id = $name[1];
        } else {
            throw new NormException('Collection name must be string');
        }

        $this->modelClass = $model;
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
    public function filter($model, $key = null)
    {
        $context = new UtilCollection([
            'collection' => $this,
            'model' => $model,
            'key' => $key,
        ]);

        return $this->apply('filter', $context, function ($context) {
            $filter = new Filter($this);
            return $filter->run($context['model'], $context['key']);
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

        $context = new UtilCollection([
            'collection' => $this,
            'model' => $model,
            'options' => $options,
        ]);

        if ($options['observer']) {
            $this->apply('save', $context, [$this, 'coreSave']);
        } else {
            $this->coreSave($context);
        }
        $context['model']->sync($context['modified']);
    }

    public function coreSave($context) {
        if ($context['options']['filter']) {
            $this->filter($context['model']);
        }
        $context['modified'] = $this->connection->persist($this->getId(), $context['model']->dump());
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

        if ($options['observer']) {
            $this->apply('remove', $context, [$this, 'coreRemove']);
        } else {
            $this->coreRemove($context);
        }
    }

    public function coreRemove($context)
    {
        $this->connection->remove($context['cursor']);
        if (null !== $context['model']) {
            $context['model']->reset(true);
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
        return $this->connection->distinct($cursor, $key);
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        return $this->connection->size($cursor, $withLimitSkip);
    }

    public function read(Cursor $cursor)
    {
        $row = $this->connection->read($cursor);
        return null === $row ? $row : $this->attach($row);
    }

    public function __debugInfo()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'connectionClass' => null === $this->connection ? null : get_class($this->connection),
            'fields' => array_keys($this->fields),
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

    /**
     * [addField description]
     * @param array|NField $metaOrField [description]
     */
    public function addField($metaOrField)
    {
        if ($metaOrField instanceof NField) {
            $field = $metaOrField;
        } else {
            $field = $this->getRepository()->resolve($metaOrField, [
                'collection' => $this,
            ]);
        }


        $this->fields[$field['name']] = $field;

        if (null === $this->firstField) {
            $this->firstField = $field['name'];
        }

        return $field;
    }

    /**
     * [formatPlain description]
     * @param  Model  $model [description]
     * @return string        [description]
     */
    protected function formatPlain($model)
    {
        if (null === $this->firstField) {
            throw new NormException('Cannot format undefined fields');
        }
        return $model[$this->firstField];
    }

    protected function formatTableFields()
    {
        return $this;
    }

    protected function formatInputFields()
    {
        return $this;
    }

    /**
     * [addFormatter description]
     * @param string          $format    [description]
     * @param string|callable $formatter [description]
     */
    public function addFormatter($format, $formatter)
    {
        if (is_string($formatter)) {
            $fmt = function ($model) use ($formatter) {
                return StringFormatter::format($formatter, $model);
            };
        } elseif (is_callable($formatter)) {
            $fmt = $formatter;
        } else {
            throw new NormException('Formatter should be callable or string format');
        }
        $this->formatters[$format] = $fmt;
        return $fmt;
    }

    /**
     * [getFormatter description]
     * @param  string   $format [description]
     * @return callable         [description]
     */
    public function getFormatter($format = 'plain')
    {
        if (!is_string($format)) {
            throw new NormException('Format key must be string');
        }
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    /**
     * [format description]
     * @param  string $format [description]
     * @param  Model  $model  [description]
     * @return string         [description]
     */
    public function format($format, $model = null)
    {
        $formatter = $this->getFormatter($format);

        if (null === $formatter) {
            throw new NormException('Formatter ' . $format . ' not found');
        }

        if (1 === func_num_args()) {
            return $formatter($this);
        } else {
            return $formatter($model);
        }
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getField($key)
    {
        if (!isset($this->fields[$key])) {
            return new NUnknown($this, $key);
        }
        return $this->fields[$key];
    }

    public function current()
    {
        return current($this->fields);
    }

    public function next()
    {
        return next($this->fields);
    }

    public function key()
    {
        return key($this->fields);
    }

    public function valid()
    {
        return null !== key($this->fields);
    }

    public function rewind()
    {
        return reset($this->fields);
    }

    public function factory($collectionId, $connectionId = '')
    {
        return null === $this->repository
            ? null
            : $this->repository->factory(
                $collectionId,
                $connectionId ?: $this->connection->getId()
            );
    }
}
