<?php

namespace Norm\Dialect;

use Norm\Collection;
use Norm\Cursor;

class MySQLDialect extends SQLDialect
{
    protected $FIELD_MAP = array(
        'Norm\Schema\Boolean' => 'BOOLEAN',
        'Norm\Schema\Float' => 'DOUBLE',
        'Norm\Schema\Integer' => 'INT',
        'Norm\Schema\Reference' => 'INT',
        'Norm\Schema\DateTime' => 'DATETIME',
        'Norm\Schema\NormArray' => 'TEXT',
        'Norm\Schema\Object' => 'TEXT',
        'Norm\Schema\Text' => 'TEXT',
        'Norm\Schema\String' => 'VARCHAR(255)',
    );

    public function grammarCount(Cursor $cursor, $foundOnly, array &$data = array())
    {

        $sql = "FROM {$cursor->getCollection()->getName()}";

        $wheres = array();

        foreach ($cursor->getCriteria() as $key => $value) {
            $wheres[] = $this->grammarExpression($key, $value, $cursor->getCollection(),$data);
        }

        if (count($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        if ($foundOnly) {

            // $sorts = $cursor->sort();
            $limit = $cursor->limit();
            $skip = $cursor->skip();

            // if (isset($sorts)) {
            //     throw new \Exception(__FILE__.':'.__LINE__.' unimplemented yet!');
            // }

            if (isset($limit) || isset($skip)) {
                $sql .= ' LIMIT '.($limit ?: 1000000000).' OFFSET '.($skip ?: 0);
            }


            $sql = 'SELECT COUNT(*) AS c FROM (SELECT * '.$sql.') AS t';
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

    public function grammarDelete(Collection $collection, array $criteria = array())
    {
        if (func_num_args() === 1) {
            return "TRUNCATE {$collection->getName()}";
        } else {
            throw new \Exception(__METHOD__.' unimplemented yet!');
        }
    }

    public function grammarCreate($name, $schema)
    {
        $fieldDefinitions = array();
        foreach ($schema as $field) {
            $found = false;
            foreach ($this->FIELD_MAP as $schemaKey => $schemaValue) {
                if ($field instanceof $schemaKey) {
                    $found = true;
                    $fieldDefinitions[] = $field['name'].' '.$schemaValue;
                    break;
                }
            }

            if (!$found) {
                $fieldDefinitions[] = $field['name'].' '.$this->FIELD_MAP['Norm\Schema\String'];
            }
        }

        $sql = 'CREATE TABLE IF NOT EXISTS '.$name.'(id INT AUTO_INCREMENT NOT NULL';

        if (!empty($fieldDefinitions)) {
            $sql .= ', '.implode(', ', $fieldDefinitions);
        }
        $sql .= ', PRIMARY KEY (id))';

        return $sql;
    }

    // public function listCollections()
    // {
    //     $statement = $this->raw->query("SHOW TABLES");
    //     $result = $statement->fetchAll();
    //     $retval = array();
    //     foreach ($result as $key => $value) {
    //         $retval[] = $value[0];
    //     }
    //     return $retval;
    // }

    // public function prepareCollection($collection) {
    //     throw new \Exception('Not implemented yet! Please recheck the method later!');
    //     $collectionName = $collection->name;
    //     $collectionSchema = $collection->schema();

    //     $sql = 'SHOW TABLES LIKE "'.$collectionName.'"';
    //     $statement = $this->raw->query($sql);
    //     $row = $statement->fetch(\PDO::FETCH_ASSOC);
    //     $tableExist = (empty($row)) ? false : true;

    //     $fields = array();
    //     if ($tableExist) {
    //         // fetch old table info
    //         $sql = 'DESCRIBE `'.$collectionName.'`';
    //         $statement = $this->raw->query($sql);
    //         $describe = $statement->fetchAll(\PDO::FETCH_ASSOC);
    //         foreach ($describe as $key => $value) {
    //             $fields[$value['name']] = $value;
    //         }
    //     }

    //     // add new fields to new table
    //     $newFields = array(
    //         'id' => array(
    //             'name' => 'id',
    //             'type' => 'INTEGER',
    //             'notnull' => '1',
    //             'dflt_value' => NULL,
    //             'pk' => '1',
    //             'autoincrement' => '1',
    //         ),
    //     );

    //     $isUpdated = false;

    //     // populate fields from old to new
    //     foreach ($collectionSchema as $schemaField) {
    //         $existingField = isset($fields[$schemaField['name']]) ? $fields[$schemaField['name']] : array();
    //         $clazz = get_class($schemaField);
    //         $type = (isset($this->FIELD_MAP[$clazz])) ? $this->FIELD_MAP[$clazz] : NULL;

    //         if (!isset($existingField['type']) || $existingField['type'] !== $type) {
    //             $isUpdated = true;
    //             $newField = array_merge($existingField, array(
    //                 'name' => $schemaField['name'],
    //                 'type' => $type,
    //             )) + array(
    //                 'notnull' => '0',
    //                 'dflt_value' => null,
    //                 'pk' => '0',
    //             );
    //             $newFields[$schemaField['name']] = $newField;
    //         } else {
    //             $newFields[$schemaField['name']] = $existingField;
    //         }
    //     }

    //     foreach ($fields as $field) {
    //         if (empty($newFields[$field['name']])) {
    //             $isUpdated = true;
    //             $newFields[$field['name']] = $field;
    //         }
    //     }

    //     if (!$isUpdated) {
    //         return;
    //     }

    //     $fieldMeta = array();
    //     $newFieldNames = array();
    //     $oldFieldNames = array();
    //     foreach ($newFields as $field) {
    //         $meta = $field['name'].' '.$field['type'];
    //         if (isset($field['pk']) && $field['pk'] == '1') {
    //             $meta .= ' PRIMARY KEY';
    //         }
    //         if (isset($field['autoincrement']) && $field['autoincrement'] == '1') {
    //             $meta .= ' AUTOINCREMENT';
    //         }
    //         if (isset($field['notnull']) && $field['notnull'] == '1') {
    //             $meta .= ' NOT NULL';
    //         }
    //         if (isset($field['dflt_value'])) {
    //             $meta .= ' DEFAULT "'.$field['dflt_value'].'"';
    //         }

    //         $fieldMeta[] = $meta;
    //         $newFieldNames[] = '"'.$field['name'].'"';
    //         if (isset($fields[$field['name']])) {
    //             $oldFieldNames[] = '"'.$field['name'].'"';
    //         } else {
    //             $oldFieldNames[] = 'NULL AS "'.$field['name'].'"';
    //         }
    //     }

    //     $tmpTable = ($tableExist) ? uniqid($collectionName.'_') : $collectionName;
    //     $sql = 'CREATE TABLE "'.$tmpTable.'" ('."\n".
    //             '    '.implode(",\n    ", $fieldMeta)."\n".
    //             ')';

    //     $this->raw->query($sql);

    //     if ($tableExist) {
    //         $sql = 'INSERT INTO "' . $tmpTable . '" (' . implode(', ', $newFieldNames) . ') SELECT '.implode(', ', $oldFieldNames).' FROM "'.$collectionName.'"';
    //         $this->raw->query($sql);
    //         $sql = 'DROP TABLE "'.$collectionName.'"';
    //         $this->raw->query($sql);
    //         $sql = 'ALTER TABLE "'.$tmpTable.'" RENAME TO "'.$collectionName.'"';
    //         $this->raw->query($sql);
    //     }

    // }
}
