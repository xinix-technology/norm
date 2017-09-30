<?php namespace Norm;

/**
 * Base class for connection instance
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Connection extends Hookable
{
    /**
     * Options associative array
     *
     * @var array
     */
    protected $options;

    /**
     * Raw object
     *
     * @var object
     */
    protected $raw;

    /**
     * Map of collections
     *
     * @var array
     */
    protected $collections = array();

    /**
     * Constructor
     *
     * @param assoc $options
     */
    public function __construct(array $options = array())
    {
        if (empty($options['name'])) {
            throw new \Exception('[Norm/Connection] Missing name, check your configuration!');
        }

        $this->options = $options;
    }

    /**
     * Getter/setter of connection options
     *
     * @param string $key Key name identifier to get single option
     *
     * @return mixed If no argument specified will get full option
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
     * Getter for connection name
     *
     * @return string Name of connection
     */
    public function getName()
    {
        return $this->options['name'];
    }

    /**
     * Getter for raw-type of connection
     *
     * @return mixed Raw-type of connection
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * Setter for raw-type of connection
     *
     * @param mixed $raw New raw connection
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
    }

    /**
     * Factory to create new collection by its name or instance
     *
     * @param string|Norm\Collection $collection Collection name or instance
     *
     * @return Norm\Collection Conllection created by factory
     */
    public function factory($collection)
    {
        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
        }

        if (!isset($this->collections[$collectionName])) {
            if (!($collection instanceof Collection)) {
                $collection = Norm::createCollection(array(
                    'name' => $collection,
                    'connection' => $this,
                ));

                $this->applyHook('norm.after.factory', $collection);
            }

            $this->collections[$collectionName] = $collection;
        }

        return $this->collections[$collectionName];
    }

    /**
     * Unmarshall single object from data source to associative array.
     * The unmarshall process is necessary due to different data type provided
     * by data source. Proper unmarshall will make sure data from data source
     * that will be consumed by Norm in the accepted form of data.
     *
     * @see Norm\Connection::marshall()
     *
     * @param mixed $object Object from data source
     *
     * @return assoc Friendly norm data
     */
    public function unmarshall($object)
    {
        if (isset($object['id'])) {
            $object['$id'] = $object['id'];
            unset($object['id']);
        }

        foreach ($object as $key => $value) {
            if ($key[0] === '_') {
                $key[0] = '$';
                $object[$key] = $value;
            }
        }

        return $object;
    }

    /**
     * Marshal single object from norm to the proper data accepted by data source.
     * Sometimes data source expects object to be persisted to it in specific form,
     * this method will transform associative array from Norm into this specific form.
     *
     * @see \Norm\Connection::unmarshall()
     *
     * @param mixed $object Norm data
     *
     * @return mixed Friendly data source object
     */
    public function marshall($object)
    {
        if (is_array($object)) {
            $result = array();

            foreach ($object as $key => $value) {
                if ($key[0] === '$') {
                    if ($key === '$id' || $key === '$type') {
                        continue;
                    }

                    $result['_'.substr($key, 1)] = $this->marshall($value);
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }
            return $result;
        } elseif ($object instanceof \Norm\Type\DateTime) {
            return $object->format('c');
        } elseif ($object instanceof \Norm\Type\NormArray) {
            return json_encode($object->toArray());
        } elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        } else {
            return $object;
        }
    }

    /**
     * Query collection and return suitable cursor
     *
     * @param string|\Norm\Collection $collection Collection name or instance
     * @param array                   $criteria   Criteria to query
     *
     * @return Norm\Cursor
     */
    abstract public function query($collection, array $criteria = array());

    /**
     * Persist specified document with current connection
     *
     * @param string|\Norm\Collection $collection Collection name or instance
     * @param array                   $document   Document to persist
     *
     * @return array Document persisted
     */
    abstract public function persist($collection, array $document);

    /**
     * Remove specified document from current connection
     * @param string|\Norm\Collection $collection Collection name or instance
     * @param array|string            $criteria   Criteria to remove or remove all data when this arg is null
     *
     * @return bool True if succeed or false if failed
     */
    abstract public function remove($collection, $criteria = null);
}
