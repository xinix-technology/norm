<?php

namespace Norm\Mysql;

use Norm\Collection;
use Norm\Model;
use Norm\Mysql\Cursor;

/**
* Norm\Mysql\QueryBuilder
*
* @author   Krisan Alfa Timur <krisan47@gmail.com>
*
*/
class QueryBuilder extends Cursor {

    function __construct() {

    }

    /**
    * Change from $id to id before we process them to our query builder
    * @access public
    * @param   array
    * @return  array
    */
    public static function prepareBeforeQuery($object) {
        $newObject = array();
        $newObject['id'] = (string) $object['$id'];
        foreach ($object as $key => $value) {
            if ($key[0] !== '$') $newObject[$key] = $value;
        }
        return $newObject;
    }

    /**
    * Build a basic INSERT INTO SQL
    * @access public
    * @param  object    instance of Norm\Collection
    * @param  object    an instance of Norm\Model
    * @return string
    */
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

    /**
    * Build a basic SELECT [collumn] FROM [table]
    * @access public
    * @param  object    an instance of Norm\Collection
    * @param  array     array you want to filter, it should be something like this
    *                       array( collname => value, collname2 => value );
    * @param  string    additional option if you want to LIMIT or something like that
    * @return string
    */
    public static function select($collectionName, $filter = NULL, $options = '') {
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

        if ($options != '') {
            $query = $query . ' ' .$options;
        }

        return $query;
    }

    /**
    * Build a basic UPDATE FROM [colname] SQL
    * @access public
    * @param  object    an instance of Norm\Collection
    * @param  object    an instance of Norm\Model
    * @return string
    */
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

    /**
    * Build a basic UPDATE FROM [colname] SQL
    * @access public
    * @param  object    an instance of Norm\Collection
    * @param  array     array you want to filter, it should be something like this
    *                       array(collname => value, collname2 => value)
    * @return string
    */
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
