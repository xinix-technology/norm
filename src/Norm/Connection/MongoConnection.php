<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;
use Norm\Type\DateTime;
use Norm\Cursor\MongoCursor;

class MongoConnection extends Connection
{
    protected $client;

    public function initialize($options)
    {
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
                throw new \Exception('[Norm] Missing database name, check your configuration!');
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

    public function getClient()
    {
        return $this->client;
    }

    public function listCollections()
    {
        $retval = array();

        $collections = $this->raw->listCollections();
        foreach ($collections as $collection) {
            $retval[] = $collection->getName();
        }

        return $retval;
    }

    // public function migrate(Collection $collection) {
    //     // noop
    // }

    public function prepare(Collection $collection, $object)
    {
        $newObject = array(
            '$id' => (string) $object['_id'],
        );
        foreach ($object as $key => $value) {
            if ($key === '_id') {
                continue;
            }
            if ($key[0] === '_') {
                $key[0] = '$';
            }
            if ($value instanceof \MongoDate) {
                $value = new DateTime('@'.$value->sec, new \DateTimeZone(date_default_timezone_get()));
            }
            $newObject[$key] = $value;
        }

        return $newObject;
    }

    public function query(Collection $collection)
    {
        return new MongoCursor($collection);
    }

    public function save(Collection $collection, Model $model)
    {
        $collectionName = $collection->name;

        $modified = $this->marshall($model->dump());

        if ($model->getId()) {
            $criteria = array(
                '_id' => new \MongoId($model->getId()),
            );
            $modified = $this->raw->$collectionName->findAndModify(
                $criteria,
                array('$set' => $modified),
                null,
                array('new' => true)
            );
            $result['ok'] = 1;
        } else {
            $result = $this->raw->$collectionName->insert($modified);
        }

        $modified = $this->prepare($collection, $modified);

        $model->sync($modified);

        return $result['ok'];
    }

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

    public function remove(Collection $collection, $model)
    {
        $collectionName = $collection->name;

        if ($model instanceof Model) {
            $criteria = array(
                '_id' => new \MongoId($model->getId()),
            );
        } else {
            $criteria = (array) $model;
        }

        return $this->raw->$collectionName->remove($criteria);
    }
}
