<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;

class MongoConnection extends Connection {
    // protected $client;

    public function initialize($options) {
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
            $database = $this->options['database'];

            $prefix = '';
            if (isset($this->options['username'])) {
                $prefix = $this->options['username'].':'.$this->options['password'].'@';
            }
            $connectionString = "mongodb://$prefix$hostname:$port/$database";
        }

        $client = new \MongoClient($connectionString);
        $this->raw = $client->$database;
    }

    // public function getClient() {
    //     return $this->client;
    // }

    public function listCollections() {
        $retval = array();

        $collections = $this->raw->listCollections();
        foreach ($collections as $collection) {
            $retval[] = $collection->getName();
        }

        return $retval;
    }

    public function prepare($object) {
        $newObject = array();
        $newObject['$id'] = (string) $object['_id'];
        foreach ($object as $key => $value) {
            if ($key[0] !== '_') {
                $newObject[$key] = $value;
            }
        }
        return $newObject;
    }

    public function query(Collection $collection) {
        $collectionName = $collection->name;

        if ($collection->filter) {
            if (isset($collection->filter['$id'])) {
                $collection->filter['_id'] = new \MongoId($collection->filter['$id']);
                unset($collection->filter['$id']);
            }
            $cursor = $this->raw->$collectionName->find($collection->filter);
        } else {
            $cursor = $this->raw->$collectionName->find();
        }

        return $cursor;
    }

    public function save(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $modified = $model->toArray(Model::FETCH_PUBLISHED);

        if ($model->getId()) {
            $criteria = array(
                '_id' => new \MongoId($model->getId()),
            );
            $modified = $this->raw->$collectionName->findAndModify($criteria, array('$set' => $modified), null, array('new' => true));
            $result['ok'] = 1;
        } else {
            $result = $this->raw->$collectionName->insert($modified);
        }

        $modified = $this->prepare($modified);

        $model->sync($modified);

        return $result['ok'];
    }

    public function remove(Collection $collection, $model) {
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