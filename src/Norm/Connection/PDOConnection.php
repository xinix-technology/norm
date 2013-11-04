<?php

namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\PDO\Cursor;

class PDOConnection extends \Norm\Connection {

    public function initialize($options) {

        $this->options = $options;

        if (isset($options['dsn'])) {
            $dsn = $options['dsn'];
        } elseif ($options['prefix'] == 'sqlite') {
            $dsn = 'sqlite:'.$options['database'];
        } else {
            $dsnArray = array();
            foreach ($options as $key => $value) {
                if ($key == 'driver' || $key == 'prefix' || $key == 'username' || $key == 'password' || $key == 'name') {
                    continue;
                }
                $dsnArray[] = "$key=$value";
            }
            $dsn = $options['prefix'].':'.implode(';', $dsnArray);
        }

        if (isset($config['username'])) {
            $this->raw = new \PDO($dsn, $config['username'], $config['password']);
        } else {
            $this->raw = new \PDO($dsn);
        }

        $this->raw->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->raw->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

    }

    public function listCollections() {
        $statement = $this->raw->query("SHOW TABLES");
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function save(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $record = $model->dump();

        if (is_null($model->getId())) {

            $fields = array();
            $placeholders = array();
            foreach ($record as $key => $value) {
                $fields[] = $key;
                $placeholders[] = ':'.$key;
            }

            $sql = 'INSERT INTO ' . $collectionName . '('.implode(', ', $fields).') VALUES('.implode(', ', $placeholders).')';

            $statement = $this->getRaw()->prepare($sql);

            $result = $statement->execute($record);

            if ($result) {
                $lastInsertId = $this->getRaw()->lastInsertId();
                $model->setId($lastInsertId);
            }

        } else {

            unset($record['$id']);
            $record['id'] = $model->getId();

            $sets = array();
            foreach ($record as $key => $value) {
                if ($key !== '$id') {
                    $sets[] = $key.' = :'.$key;
                }
            }

            $sql = 'UPDATE '.$collectionName.' SET '.implode(', ', $sets) . ' WHERE id = :id';

            $statement = $this->getRaw()->prepare($sql);
            $result = $statement->execute($record);
        }

        return $result;
    }

    public function query(Collection $collection) {
        $collectionName = $collection->name;

        // FIXME reekoheek should we use query builder?
        // $q = new Query($this);
        // $q->from($collectionName);
        // $q->where($collection->filter);
        // $result = $q->result();

        $filter = $collection->filter ?: array();
        if (isset($filter['$id'])) {
            $filter['id'] = $filter['$id'];
            unset($filter['$id']);
        }

        $sql = 'SELECT * FROM '. $collectionName;

        $wheres = array();
        foreach ($filter as $key => $value) {
            $wheres[] = $key . ' = :' . $key;
        }
        if (count($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        $statement = $this->getRaw()->prepare($sql);

        $statement->execute($filter);

        return new Cursor($statement);
    }

    public function prepare($object) {
        if (!is_array($object)) { return null; }

        if (isset($object['id'])) {
            $object['$id'] = $object['id'];
            unset($object['id']);
        }
        return $object;
    }

    public function remove(Collection $collection, $model) {
        $collectionName = $collection->name;

        $sql = 'DELETE FROM '.$collectionName.' WHERE id = :id';

        $statement = $this->getRaw()->prepare($sql);
        $result = $statement->execute(array(
            'id' => $model->getId()
        ));

        return $result;
    }
}