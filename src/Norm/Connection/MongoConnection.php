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

    public function getCollection($name) {
        new Collection($name, $this);
        return $this->db->$name;
    }

    public function factory($collectionName) {
        return new Collection(array(
            'name' => $collectionName,
            'connection' => $this,
        ));
    }

    public function query(Collection $collection) {
        $collectionName = $collection->name;

        if ($collection->filter) {
            if (isset($collection->filter['_id']) AND !is_object($collection->filter['_id'])) {
                $collection->filter['_id'] = new \MongoId($collection->filter['_id']);
            }
            $cursor = $this->db->$collectionName->find($collection->filter);
        } else {
            $cursor = $this->db->$collectionName->find();
        }

        return $cursor;
    }

    public function save(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $modified = $model->toArray();
        unset($modified['_id']);
        $this->db->$collectionName->update(array('_id' => $model->get('_id')), array('$set' => $modified));
    }

    public function remove(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $this->db->$collectionName->remove(array('_id' => $model->get('_id')));
    }

}