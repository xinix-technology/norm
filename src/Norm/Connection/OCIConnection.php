<?php

namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\Cursor\OCICursor as Cursor;
use Norm\Dialect\OracleDialect;

class OCIConnection extends \Norm\Connection
{

    protected $dialect;

    public function initialize($options)
    {

        $defaultOptions = array(
            'username' => null,
            'password' => null,
            'dbname' => null,
            'charset' => null,
            'mode' => null
        );

        $this->options = array_merge($defaultOptions, $options);
        $this->raw = oci_connect(
            $this->options['username'],
            $this->options['password'],
            $this->options['dbname'],
            $this->options['charset'],
            $this->options['mode']
        );
        $this->prepareInit();

        $this->dialect = new OracleDialect($this);
    }

    private function prepareInit()
    {
        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        oci_execute($stid);
        oci_free_statement($stid);

        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_SORT = BINARY_CI");
        oci_execute($stid);
        oci_free_statement($stid);

        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_COMP = LINGUISTIC");
        oci_execute($stid);
        oci_free_statement($stid);
    }

    public function listCollections()
    {
        throw new \Exception('Not implemented!');
    }

    public function prepare(Collection $collection, $object)
    {
        $object = array_change_key_case($object, CASE_LOWER);
        $newObject = array(
            '$id' => $object['id'],
        );
        foreach ($object as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            if ($key[0] === '_') {
                $key[0] = '$';
            }
            $newObject[$key] = $value;
        }

        return $newObject;
    }

    public function query(Collection $collection)
    {
        return new Cursor($collection);
    }

    public function save(Collection $collection, Model $model)
    {
        $collectionName = $collection->name;
        // $schemes = $collection->schema();
        $data = $this->marshall($model->dump());
        $result = false;

        if (is_null($model->getId())) {
            $id = $this->insert($collectionName, $data);
            if ($id) {
                $model->setId($id);
                $result = true;
            }
        } else {
            $data['id'] = $model->getId();
            $result = $this->update($collectionName, $data);

            if ($result) {
                $result = true;
            }
        }
        return $result;
    }

    public function remove(Collection $collection, $model)
    {
        $collectionName = $collection->name;
        $id = $model->getId();

        $sql = 'DELETE FROM '.$collectionName.' WHERE id = :id';

        $stid = oci_parse($this->raw, $sql);
        oci_bind_by_name($stid, ":id", $id);
        $result = oci_execute($stid);
        oci_free_statement($stid);

        return $result;
    }

    public function insert($collectionName, $data)
    {
        $id = 0;
        $sql = $this->dialect->grammarInsert($collectionName, $data);

        $stid = oci_parse($this->raw, $sql);
        oci_bind_by_name($stid, ":id", $id);

        foreach ($data as $key => $value) {
            oci_bind_by_name($stid, ":".$key, $data[$key]);
        }
        oci_execute($stid);
        oci_free_statement($stid);
        return $id;
    }

    public function update($collectionName, $data)
    {
        $sql = $this->dialect->grammarUpdate($collectionName, $data);

        $stid = oci_parse($this->raw, $sql);
        oci_bind_by_name($stid, ":id", $data['id']);

        foreach ($data as $key => $value) {
            oci_bind_by_name($stid, ":".$key, $data[$key]);
        }

        $result = oci_execute($stid);
        oci_free_statement($stid);
        return $result;
    }

    public function marshall($object)
    {
        if ($object instanceof \Norm\Type\DateTime) {
            return $object->format('Y-m-d H:i:s');
        } elseif (is_array($object)) {
            $result = array();
            foreach ($object as $key => $value) {
                if ($key[0] === '$') {
                    if ($key === '$id' || $key === '$type') {
                        continue;
                    } else {
                        $result[substr($key, 1)] = $this->marshall($value);
                    }
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }
            return $result;
        } else {
            return parent::marshall($object);
        }
    }

    public function getDialect()
    {
        return $this->dialect;
    }
}
