<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;
use Norm\Type\DateTime;
use Norm\Cursor\MongoCursor;

class MongoConnection extends Connection
{
    /**
     * MongoDB client object
     * @var MongoClient
     */
    protected $client;

    /**
     * @see Norm\Connection
     */
    public function __construct($options)
    {
        parent::__construct($options);

        $defaultOptions = array(
            'hostname' => \MongoClient::DEFAULT_HOST,
            'port' => \MongoClient::DEFAULT_PORT,
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
                throw new \Exception('[Norm/MongoConnection] Missing database name, check your configuration!');
            }

            $prefix = '';
            if (isset($this->options['username'])) {
                $prefix = $this->options['username'].':'.$this->options['password'].'@';
            }
            $connectionString = "mongodb://$prefix$hostname:$port/$database";
        }

        $this->client = new \MongoClient($connectionString);
        $this->raw = $this->client->$database;
    }

    /**
     * see Norm\Connection::query()
     */
    public function query($collection, array $criteria = array())
    {
        return new MongoCursor($this->factory($collection), $criteria);
    }

    /**
     * see Norm\Connection::persist()
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
                '_id' => new \MongoId($document['$id']),
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
                throw new \Exception($retval['errmsg']);
            }
        }

        return $this->unmarshall($marshalledDocument);
    }

    /**
     * see Norm\Connection::remove()
     */
    public function remove($collection, $criteria = null)
    {
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        }

        if (func_num_args() === 1) {
            $result = $this->raw->$collection->remove();
        } else {
            if ($criteria instanceof \Norm\Model) {
                $criteria = $criteria->getId();
            }

            if (is_string($criteria)) {
                $criteria = array(
                    '_id' => new \MongoId($criteria),
                );
            } elseif (!is_array($criteria)) {
                throw new \Exception('[Norm/Connection] Cannot remove with specified criteria. Criteria must be array, string, or model');
            }

            $result = $this->raw->$collection->remove($criteria);
        }

        if ($result['ok'] != 1) {
            throw new \Exception($result['errmsg']);
        }
    }

    /**
     * Get MongoDB client
     * @return MongoClient MongoDB client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @see Norm\Connection::unmarshall()
     */
    public function unmarshall($object)
    {
        if (isset($object['_id'])) {
            $object['$id'] = (string) $object['_id'];
            unset($object['_id']);
        }

        foreach ($object as $key => &$value) {

            if ($value instanceof \MongoDate) {
                $value = new DateTime('@'.$value->sec, new \DateTimeZone(date_default_timezone_get()));
            } elseif ($value instanceof \MongoId) {
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
     * @see Norm\Connection::marshall()
     */
    public function marshall($object)
    {
        if ($object instanceof \DateTime) {
            return new \MongoDate($object->getTimestamp());
        } elseif ($object instanceof \Norm\Type\NormArray) {
            return $object->toArray();
        } elseif ($object instanceof \Norm\Type\Object) {
            return $object->toObject();
        } else {
            return parent::marshall($object);
        }
    }

    // public function listCollections()
    // {
    //     $retval = array();

    //     $collections = $this->raw->listCollections();
    //     foreach ($collections as $collection) {
    //         $retval[] = $collection->getName();
    //     }

    //     return $retval;
    // }
}
