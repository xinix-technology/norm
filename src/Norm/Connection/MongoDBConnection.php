<?php namespace Norm\Connection;


use DateTime;
use Exception;
use Norm\Model;
use MongoDB\Driver\Manager as MongoClient;
use MongoDB\BSON\UTCDateTime as MongoDate;
use MongoDB\BSON\ObjectId as MongoId;
use Norm\Connection;
use Norm\Collection;
use Norm\Type\NObject;
use Norm\Type\NDateTime as NormDateTime;
use Norm\Type\NormArray;
use Norm\Cursor\MongoCursor;

/**
 * Mongo Connection.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class MongoDBConnection extends Connection
{
    /**
     * MongoDB client object
     *
     * @var \MongoClient
     */
    protected $client;

    /**
     * {@inheritDoc}
     */
    public function __construct($options)
    {
        parent::__construct($options);

        $defaultOptions = array(
            'hostname' => '127.0.0.1',
            'port' => '27017',
        );

        $this->options = $options + $defaultOptions;

        if (isset($this->options['connectionString'])) {
            $connectionString = $this->options['connectionString'];
        } else {
            $hostname = $this->options['hostname'];
            $port = $this->options['port'];

            if (isset($this->options['database'])) {
                $database = $this->options['database'];
            } else {
                throw new Exception('[Norm/MongoConnection] Missing database name, check your configuration!');
            }

            $prefix = '';

            if (isset($this->options['username'])) {
                $prefix = $this->options['username'].':'.$this->options['password'].'@';
            }

            $connectionString = "mongodb://$prefix$hostname:$port/$database";
        }

        $this->client = new MongoClient($connectionString);
        $this->raw = $this->client->$database;
    }

    /**
     * {@inheritDoc}
     */
    public function query($collection, array $criteria = array())
    {
        return new MongoCursor($this->factory($collection), $criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function persist($collection, array $document)
    {
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        }

        $marshalledDocument = $this->marshall($document);

        $result = false;

        if (isset($document['$id'])) {
            $criteria = array(
                '_id' => new MongoId($document['$id']),
            );

            $marshalledDocument = $this->raw->$collection->findAndModify(
                $criteria,
                array('$set' => $marshalledDocument),
                null,
                array('new' => true)
            );
        } else {
            $retval = $this->raw->$collection->insert($marshalledDocument);

            if (!$retval['ok']) {
                throw new Exception($retval['errmsg']);
            }
        }

        return $this->unmarshall($marshalledDocument);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($collection, $criteria = null)
    {
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        }

        if (func_num_args() === 1) {
            $result = $this->raw->$collection->remove();
        } else {
            if ($criteria instanceof Model) {
                $criteria = $criteria->getId();
            }

            if (is_string($criteria)) {
                $criteria = array(
                    '_id' => new MongoId($criteria),
                );
            } elseif (! is_array($criteria)) {
                throw new Exception('[Norm/Connection] Cannot remove with specified criteria. Criteria must be array, string, or model');
            }

            $result = $this->raw->$collection->remove($criteria);
        }

        if ((int) $result['ok'] !== 1) {
            throw new Exception($result['errmsg']);
        }
    }

    /**
     * Get MongoDB client
     *
     * @return MongoClient MongoDB client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritDoc}
     */
    public function unmarshall($object)
    {
        if (isset($object['_id'])) {
            $object['$id'] = (string) $object['_id'];
            unset($object['_id']);
        }

        foreach ($object as $key => &$value) {
            if ($value instanceof MongoDate) {
                $value = new DateTime('@'.$value->sec);
            } elseif ($value instanceof MongoId) {
                $value = (string) $value;
            }

            if ($key[0] === '_') {
                unset($object[$key]);
                $key[0] = '$';
                $object[$key] = $value;
            }
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function marshall($object)
    {
        if ($object instanceof NormDateTime) {
            return new MongoDate($object->getTimestamp());
        } elseif ($object instanceof NormArray) {
            return $object->toArray();
        } elseif ($object instanceof NObject) {
            return $object->toObject();
        } else {
            return parent::marshall($object);
        }
    }
}
