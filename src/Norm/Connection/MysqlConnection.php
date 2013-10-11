<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;
use Norm\Norm;
use Norm\Mysql\Cursor;
use Norm\Mysql\QueryBuilder;

class MysqlConnection extends Connection {
    protected $client;
    protected $db;

    public function initialize($options) {
        $defaultOptions = array(
            'hostname' => 'localhost',
            'port' => '3306',
            'debug' => true
        );

        $this->options = $options + $defaultOptions;

        $hostname = $this->options['hostname'];
        $port = $this->options['port'];
        $database = $this->options['database'];
        $username = $this->options['username'];
        $password = $this->options['password'];

        $this->db = new \PDO("mysql:host=$hostname:$port;dbname=$database;charset=utf8", $username, $password);

        if ($this->options['debug']) {
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }

    }

    public function getDB() {
        return $this->db;
    }

    public function prepare($object) {
        if (!is_array($object)) { return null; }
        foreach ($object as $key => $value) {
            if (isset($value['id'])) {
                $object[$key]['$id'] = $value['id'];
                unset($object[$key]['id']);
            }
        }
        return $object;
    }

    public function dumpAll(Collection $collection) {
        $collectionName = $collection->name;
        $query = QueryBuilder::select($collectionName, array());
        $statement = $this->db->query($query);
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $retVal = $this->prepare($results);
        return $retVal;
    }

    public function query(Collection $collection) {
        $collectionName = $collection->name;
        $cursor = null;
        if ($collection->filter) {
            $query = QueryBuilder::select($collectionName, $collection->filter, 'LIMIT 1');
            $statement = $this->db->query($query);
            $result = $this->prepare($statement->fetchAll(\PDO::FETCH_ASSOC));
            $cursor = new Cursor($result);
        } else {
            $cursor = new Cursor($this->dumpAll($collection));
        }

        $collection->filter = null;
        return $cursor;
    }

    public function listCollections() {
        $statement = $this->db->query("SHOW TABLES");
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function save(Collection $collection, Model $model) {
        if ($model->get('$id') == '') {
            $model->set('$id', md5(uniqid(time(), true)));
            $model->setId($model->get('$id'));
            $query = QueryBuilder::insertInto($collection, $model);
            $affectedRows = $this->db->exec($query);
        } else {
            $query = QueryBuilder::update($collection, $model);
            $affectedRows = $this->db->exec($query);
        }

        return $affectedRows;
    }

    public function remove(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $id = null;

        if (count($model->dump()) > 0) {
            $list = $model->get(0);
            $id = $list['$id'];
        }

        $filter = array('id' => $id);

        $query = QueryBuilder::deleteFrom($collection, $filter);

        $affectedRows = $this->db->exec($query);

        $collection->filter = null;

        return $affectedRows;
    }

}

