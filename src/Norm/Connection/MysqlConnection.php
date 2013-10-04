<?php

namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Model;
use Norm\Norm;
use Norm\Helpers\Generator;

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

    public function getOne(Collection $collection, $cursor) {
        $collectionName = $collection->name;
        if (count($cursor) > 0) {
            $id = $cursor[0]["\$id"];
            $statement = $this->db->query("SELECT * FROM $collectionName WHERE \$id = '$id'");
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = null;
        }
        return $result;
    }

    public function dumpAll(Collection $collection) {
        $collectionName = $collection->name;
        $statement = $this->db->query("SELECT * FROM $collectionName");
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function query(Collection $collection) {
        $collectionName = $collection->name;
        if ($collection->filter) {
            $colname = '';
            $val = '';
            foreach ($collection->filter as $key => $value) {
                $colname = $key;
                $val = $value;
            }
            $statement = $this->db->query("SELECT * FROM $collectionName WHERE $colname='$val'");
            $cursor = $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $cursor = $this->dumpAll($collection);
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
        $collectionName = $collection->name;

        if ($model->get('$id') == '') {
            $model->set('$id', Generator::genId());
            $id = $model->get('$id');
            $model->setId($id);

            $lists = $model->dump();

            $colName = '';
            $values = '';

            foreach ($lists as $key => $value) {
                $colName .= $key . ', ';
                $values .= "'$value'" . ', ';
            }

            $colName = preg_replace('/, $/i', '', $colName);
            $values = preg_replace('/, $/i', '', $values);

            $query = "INSERT INTO $collectionName ($colName) VALUES ($values)";

            $affected_rows = $this->db->exec($query);
        } else {
            $id = $model->get('$id');

            $lists = $model->dump();

            $updated = '';

            foreach ($lists as $key => $value) {
                $updated .= "$key='$value', ";
            }

            $updated = preg_replace('/, $/i', '', $updated);

            $query = "UPDATE $collectionName SET $updated WHERE \$id='$id'";

            $affected_rows = $this->db->exec($query);
        }


        return $affected_rows;
    }

    public function remove(Collection $collection, $model) {
        $collectionName = $collection->name;

        $id = $model->getId();

        $query = "DELETE FROM $collectionName WHERE \$id='$id'";

        $affected_rows = $this->db->exec($query);

        $collection->filter = null;

        return $affected_rows;
    }

}
