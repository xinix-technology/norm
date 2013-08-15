<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;

class MongoConnection extends Connection {
    protected $client;
    protected $db;

    public function initialize($options) {
        $defaultOptions = array(
            'hostname' => \MongoClient::DEFAULT_HOST,
            'port' => \MongoClient::DEFAULT_PORT,
        );
        $this->options = $options + $defaultOptions;

        $hostname = $this->options['hostname'];
        $port = $this->options['port'];
        $database = $this->options['database'];

        if (isset($this->options['connectionString'])) {
            $connectionString = $this->options['connectionString'];
        } else {
            $prefix = '';
            if (isset($this->options['username'])) {
                $prefix = $this->options['username'].':'.$this->options['password'].'@';
            }
            $connectionString = "mongodb://$prefix$hostname:$port/$database";
        }

        $this->client = new \MongoClient($connectionString);
        $this->db = $this->client->$database;
    }

    public function getClient() {
        return $this->client;
    }

    public function getDB() {
        return $this->db;
    }

    public function listCollections() {
        $retval = array();

        $collections = $this->db->listCollections();
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
            $cursor = $this->db->$collectionName->find($collection->filter);
        } else {
            $cursor = $this->db->$collectionName->find();
        }

        $collection->filter = null;

        return $cursor;
    }

    public function save(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $modified = $model->toArray(Model::FETCH_PUBLISHED);

        if ($model->getId()) {
            $criteria = array(
                '_id' => new \MongoId($model->getId()),
            );
            $modified = $this->db->$collectionName->findAndModify($criteria, $modified, null, array('new' => true));
            $result['ok'] = 1;
        } else {
            $result = $this->db->$collectionName->insert($modified);
        }

        $modified = $this->prepare($modified);

        $model->sync($modified);

        $collection->filter = null;

        $result = $result['ok'];
        return $result;
    }

    public function remove(Collection $collection, Model $model) {
        $collectionName = $collection->name;

        $criteria = array(
            '_id' => new \MongoId($model->getId()),
        );
        $result = $this->db->$collectionName->remove($criteria);

        $collection->filter = null;

        return $result;
    }

}