<?php

namespace Norm\Dialect;

use Norm\Cursor;

class SqliteDialect extends SQLDialect
{
    protected $FIELD_MAP = array(
        'Norm\Schema\Boolean' => 'BOOL',
        'Norm\Schema\Float' => 'DOUBLE',
        'Norm\Schema\Integer' => 'INTEGER',
        'Norm\Schema\Reference' => 'INTEGER',
        'Norm\Schema\DateTime' => 'DATETIME',
        'Norm\Schema\NormArray' => 'TEXT',
        'Norm\Schema\Object' => 'TEXT',
        'Norm\Schema\Text' => 'TEXT',
        'Norm\Schema\String' => 'VARCHAR',
    );

    public function grammarCount(Cursor $cursor, $foundOnly, array &$data = array())
    {

        $sql = "FROM {$cursor->getCollection()->getName()}";

        $wheres = array();

        foreach ($cursor->getCriteria() as $key => $value) {
            $wheres[] = $this->collection->connection->getDialect()->grammarExpression($key, $value, $data);
        }

        if (count($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        if ($foundOnly) {

            $sorts = $cursor->sort();
            $limit = $cursor->limit();
            $skip = $cursor->skip();

            if (isset($sorts)) {
                throw new \Exception(__FILE__.':'.__LINE__.' unimplemented yet!');
            }

            if (isset($limit) || isset($skip)) {
                $sql .= ' LIMIT '.($limit ?: -1).' OFFSET '.($skip ?: 0);
            }


            $sql = 'SELECT COUNT(*) AS c FROM (SELECT * '.$sql.')';
        } else {
            $sql = 'SELECT COUNT(*) AS c '.$sql;
        }

        return $sql;
    }

    public function grammarDistinct(Cursor $cursor,$key){
        $sql = "FROM {$cursor->getCollection()->getName()}";
        $sql = 'SELECT DISTINCT('. $key .') AS c '.$sql;

        return $sql;

    }


//     public function listCollections() {
//         $statement = $this->raw->query("SELECT * FROM sqlite_master WHERE type='table'");
//         $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
//         $retval = array();
//         foreach ($result as $key => $value) {
//             $retval[] = $value['name'];
//         }
//         return $retval;
//     }

//     public function prepareCollection($collection) {
//         $collectionName = $collection->name;
//         $collectionSchema = $collection->schema();

//         $sql = 'SELECT * FROM sqlite_master WHERE name="'.$collectionName.'"';
//         $statement = $this->raw->query($sql);
//         $row = $statement->fetch(\PDO::FETCH_ASSOC);
//         $tableExist = (empty($row)) ? false : true;

//         // fetch old table info
//         $sql = 'PRAGMA table_info("'.$collectionName.'")';
//         $statement = $this->raw->query($sql);
//         $describe = $statement->fetchAll(\PDO::FETCH_ASSOC);
//         $fields = array();
//         foreach ($describe as $key => $value) {
//             $fields[$value['name']] = $value;
//         }

//         // add new fields to new table
//         $newFields = array(
//             'id' => array(
//                 'name' => 'id',
//                 'type' => 'INTEGER',
//                 'notnull' => '1',
//                 'dflt_value' => NULL,
//                 'pk' => '1',
//                 'autoincrement' => '1',
//             ),
//         );

//         $isUpdated = false;

//         // populate fields from old to new
//         foreach ($collectionSchema as $schemaField) {
//             $existingField = isset($fields[$schemaField['name']]) ? $fields[$schemaField['name']] : array();
//             $clazz = get_class($schemaField);
//             $type = (isset($this->FIELD_MAP[$clazz])) ? $this->FIELD_MAP[$clazz] : NULL;

//             if (!isset($existingField['type']) || $existingField['type'] !== $type) {
//                 $isUpdated = true;
//                 $newField = array_merge($existingField, array(
//                     'name' => $schemaField['name'],
//                     'type' => $type,
//                 )) + array(
//                     'notnull' => '0',
//                     'dflt_value' => null,
//                     'pk' => '0',
//                 );
//                 $newFields[$schemaField['name']] = $newField;
//             } else {
//                 $newFields[$schemaField['name']] = $existingField;
//             }
//         }

//         foreach ($fields as $field) {
//             if (empty($newFields[$field['name']])) {
//                 $isUpdated = true;
//                 $newFields[$field['name']] = $field;
//             }
//         }

//         if (!$isUpdated) {
//             return;
//         }

//         $fieldMeta = array();
//         $newFieldNames = array();
//         $oldFieldNames = array();
//         foreach ($newFields as $field) {
//             $meta = $field['name'].' '.$field['type'];
//             if (isset($field['pk']) && $field['pk'] == '1') {
//                 $meta .= ' PRIMARY KEY';
//             }
//             if (isset($field['autoincrement']) && $field['autoincrement'] == '1') {
//                 $meta .= ' AUTOINCREMENT';
//             }
//             if (isset($field['notnull']) && $field['notnull'] == '1') {
//                 $meta .= ' NOT NULL';
//             }
//             if (isset($field['dflt_value'])) {
//                 $meta .= ' DEFAULT "'.$field['dflt_value'].'"';
//             }

//             $fieldMeta[] = $meta;
//             $newFieldNames[] = '"'.$field['name'].'"';
//             if (isset($fields[$field['name']])) {
//                 $oldFieldNames[] = '"'.$field['name'].'"';
//             } else {
//                 $oldFieldNames[] = 'NULL AS "'.$field['name'].'"';
//             }
//         }

//         $tmpTable = ($tableExist) ? uniqid($collectionName.'_') : $collectionName;
//         $sql = 'CREATE TABLE "'.$tmpTable.'" ('."\n".
//                 '    '.implode(",\n    ", $fieldMeta)."\n".
//                 ')';
//         $this->raw->query($sql);

//         if ($tableExist) {
//             $sql = 'INSERT INTO "' . $tmpTable . '" (' . implode(', ', $newFieldNames) . ') SELECT '.implode(', ', $oldFieldNames).' FROM "'.$collectionName.'"';
//             $this->raw->query($sql);
//             $sql = 'DROP table "'.$collectionName.'"';
//             $this->raw->query($sql);
//             $sql = 'ALTER TABLE "'.$tmpTable.'" RENAME TO "'.$collectionName.'"';
//             $this->raw->query($sql);
//         }

//     }
}
