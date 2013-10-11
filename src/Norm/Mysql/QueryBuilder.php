<?php

namespace Norm\Mysql;

use Norm\Collection;
use Norm\Model;
use Norm\Mysql\Cursor;

/**
*
*/
class QueryBuilder extends Cursor {

    function __construct() {

    }

    public static function prepareBeforeQuery($object) {
        $newObject = array();
        $newObject['id'] = (string) $object['$id'];
        foreach ($object as $key => $value) {
            if ($key[0] !== '$') $newObject[$key] = $value;
        }
        return $newObject;
    }

    public static function insertInto(Collection $collection, Model $model) {
        $collectionName = $collection->name;

        $lists = $model->dump();
        $lists = self::prepareBeforeQuery($lists);

        $columnName = '';
        $values = '';

        foreach ($lists as $key => $value) {
            $columnName .= $key . ', ';
            $values .= "'$value'" . ', ';
        }

        $columnName = preg_replace('/, $/i', '', $columnName);
        $values = preg_replace('/, $/i', '', $values);

        $query = "INSERT INTO $collectionName ($columnName) VALUES ($values)";

        return $query;
    }

    public static function select($collectionName, $filter = array(), $arguments = '') {
        if (count($filter) > 0) {
            $cursor = new Cursor($filter);
            $whereAs = '';

            if ($cursor->hasNext()) {
                $lists = $cursor->getNext();
                foreach ($lists as $key => $value) {
                    $whereAs .= "$key='$value' AND ";
                }
            }

            $whereAs = preg_replace('/ AND $/i', '', $whereAs);

            $query = "SELECT * FROM $collectionName WHERE $whereAs";
        } else {
            $query = "SELECT * FROM $collectionName";
        }

        $query = $query . $arguments;

        return $query;
    }

    public static function update(Collection $collection, Model $model) {
        $collectionName = $collection->name;
        $id = $model->get('$id');
        $lists = $model->dump();
        $lists = self::prepareBeforeQuery($lists);
        $updated = '';

        foreach ($lists as $key => $value) {
            $updated .= "$key='$value', ";
        }

        $updated = preg_replace('/, $/i', '', $updated);

        $query = "UPDATE $collectionName SET $updated WHERE id='$id'";

        return $query;
    }

    public static function deleteFrom(Collection $collection, $criteria) {
        $collectionName = $collection->name;
        $cursor = new Cursor($criteria);
        $whereAs = '';

        if ($cursor->hasNext()) {
            $lists = $cursor->getNext();
            foreach ($lists as $key => $value) {
                $whereAs .= "$key='$value' AND ";
            }
        }

        $whereAs = preg_replace('/ AND $/i', '', $whereAs);

        $query = "DELETE FROM $collectionName WHERE $whereAs";

        return $query;
    }
}
