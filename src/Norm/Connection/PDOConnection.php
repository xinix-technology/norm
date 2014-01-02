<?php

namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\PDO\Cursor;

class PDOConnection extends \Norm\Connection {

    protected $dialect;

    public function initialize($options) {

        $this->options = $options;
        if (isset($options['dsn'])) {
            $dsn = $options['dsn'];
        } elseif ($options['prefix'] === 'sqlite') {
            $dsn = 'sqlite:'.$options['database'];
        } else {
            $dsnArray = array();
            foreach ($options as $key => $value) {
                if ($key === 'driver' || $key === 'prefix' || $key === 'username' || $key === 'password' || $key === 'name' || $key === 'dialect') {
                    continue;
                }
                $dsnArray[] = "$key=$value";
            }
            $dsn = $options['prefix'].':'.implode(';', $dsnArray);
        }

        if (isset($options['username'])) {
            $this->raw = new \PDO($dsn, $options['username'], $options['password']);
        } else {
            $this->raw = new \PDO($dsn);
        }

        $this->raw->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->raw->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        if (isset($options['dialect'])) {
            $Dialect = $options['dialect'];
        } else {
            $Dialect = '\\Norm\\Dialect\\SQLDialect';
        }
        $this->dialect = new $Dialect($this);

    }

    public function listCollections() {
        return $this->dialect->listCollections();
    }

    // public function migrate(Collection $collection) {
    //     if (!$this->hasCollection($collection->name)) {
    //         $grammarCreate = $this->dialect->grammarCreate($collection->name, $collection->schema);

    //         $this->raw->query($grammarCreate);
    //     }
    // }

    public function save(Collection $collection, Model $model) {
        if (!empty($this->options['autocreate'])) {
            $this->dialect->prepareCollection($collection);
        }

        $collectionName = $collection->name;
        $schemes = $collection->schema();

        $record = $model->dump();

        if (is_null($model->getId())) {

            $fields = array();
            $placeholders = array();
            foreach ($record as $key => $value) {
                if ($key === '$id') {
                    continue;
                }

                if (array_key_exists($key, $schemes)) {
                    $schema = $schemes[$key];
                    if ($schema instanceof \Norm\Schema\DateTime) {
                        $record[$key] = date('Y-m-d H:i:s', strtotime($value));
                    }
                }

                if ($key[0] === '$') {
                    $k = '_'.substr($key, 1);
                    $record[$k] = $value;
                    unset($record[$key]);
                } else {
                    $k = $key;
                    $sets[] = $k.' = :'.$k;
                }

                $fields[] = $k;
                $placeholders[] = ':'.$k;
            }

            $sql = 'INSERT INTO ' . $collectionName . '('.implode(', ', $fields).') VALUES('.implode(', ', $placeholders).')';

            $statement = $this->getRaw()->prepare($sql);

            $result = $statement->execute($record);

            if ($result) {
                $lastInsertId = $this->getRaw()->lastInsertId();
                $model->setId($lastInsertId);
            }

        } else {
            $sets = array();
            foreach ($record as $key => $value) {
                if ($key === '$id') {
                    $record['id'] = $value;
                    unset($record['$id']);
                    continue;
                }

                // if (array_key_exists($key, $schemes)) {
                //     $schema = $schemes[$key];
                //     if ($schema instanceof \Norm\Schema\DateTime) {
                //         $record[$key] = $value;
                //     }
                // }

                if ($key[0] === '$') {
                    $k = '_'.substr($key, 1);
                    $record[$k] = $value;
                    $sets[] = $k.' = :'.$k;
                    unset($record[$key]);
                } else {
                    $k = $key;
                    $sets[] = $k.' = :'.$k;
                }
            }

            $sql = 'UPDATE '.$collectionName.' SET '.implode(', ', $sets) . ' WHERE id = :id';

            $statement = $this->getRaw()->prepare($sql);

            $result = $statement->execute($record);
        }

        return $result;
    }

    public function query(Collection $collection) {
        if (!empty($this->options['autocreate'])) {
            $this->dialect->prepareCollection($collection);
        }

        return new Cursor($collection);
    }

    public function prepare(Collection $collection, $object) {
        $newObject = array(
            '$id' => $object['id'],
        );
        foreach ($object as $key => $value) {
            if ($key === 'id') continue;
            if ($key[0] === '_') {
                $key[0] = '$';
            }
            $newObject[$key] = $value;
        }

        return $newObject;
    }

    public function remove(Collection $collection, $model) {
        if (!empty($this->options['autocreate'])) {
            $this->dialect->prepareCollection($collection);
        }

        $collectionName = $collection->name;

        $sql = 'DELETE FROM '.$collectionName.' WHERE id = :id';

        $statement = $this->getRaw()->prepare($sql);
        $result = $statement->execute(array(
            'id' => $model->getId()
        ));

        return $result;
    }

    public function getDialect() {
        return $this->dialect;
    }
}