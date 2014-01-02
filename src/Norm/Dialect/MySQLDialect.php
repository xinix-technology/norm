<?php

namespace Norm\Dialect;

class MySQLDialect extends SQLDialect {
    protected $FIELD_MAP = array(
        'Norm\Schema\Boolean' => 'TINYINT',
        'Norm\Schema\DateTime' => 'DATETIME',
        'Norm\Schema\Integer' => 'INT',
        'Norm\Schema\Password' => 'VARCHAR',
        'Norm\Schema\Reference' => 'INT',
        'Norm\Schema\String' => 'VARCHAR',
        'Norm\Schema\Text' => 'TEXT',
    );

    public function listCollections() {
        $statement = $this->raw->query("SHOW TABLES");
        $result = $statement->fetchAll();
        $retval = array();
        foreach ($result as $key => $value) {
            $retval[] = $value[0];
        }
        return $retval;
    }

    public function prepareCollection($collection) {
        throw new \Exception('Not implemented yet! Please recheck the method later!');
        $collectionName = $collection->name;
        $collectionSchema = $collection->schema();

        $sql = 'SHOW TABLES LIKE "'.$collectionName.'"';
        $statement = $this->raw->query($sql);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $tableExist = (empty($row)) ? false : true;

        $fields = array();
        if ($tableExist) {
            // fetch old table info
            $sql = 'DESCRIBE `'.$collectionName.'`';
            $statement = $this->raw->query($sql);
            $describe = $statement->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($describe as $key => $value) {
                $fields[$value['name']] = $value;
            }
        }

        // add new fields to new table
        $newFields = array(
            'id' => array(
                'name' => 'id',
                'type' => 'INTEGER',
                'notnull' => '1',
                'dflt_value' => NULL,
                'pk' => '1',
                'autoincrement' => '1',
            ),
        );

        $isUpdated = false;

        // populate fields from old to new
        foreach ($collectionSchema as $schemaField) {
            $existingField = isset($fields[$schemaField['name']]) ? $fields[$schemaField['name']] : array();
            $clazz = get_class($schemaField);
            $type = (isset($this->FIELD_MAP[$clazz])) ? $this->FIELD_MAP[$clazz] : NULL;

            if (!isset($existingField['type']) || $existingField['type'] !== $type) {
                $isUpdated = true;
                $newField = array_merge($existingField, array(
                    'name' => $schemaField['name'],
                    'type' => $type,
                )) + array(
                    'notnull' => '0',
                    'dflt_value' => null,
                    'pk' => '0',
                );
                $newFields[$schemaField['name']] = $newField;
            } else {
                $newFields[$schemaField['name']] = $existingField;
            }
        }

        foreach ($fields as $field) {
            if (empty($newFields[$field['name']])) {
                $isUpdated = true;
                $newFields[$field['name']] = $field;
            }
        }

        if (!$isUpdated) {
            return;
        }

        $fieldMeta = array();
        $newFieldNames = array();
        $oldFieldNames = array();
        foreach ($newFields as $field) {
            $meta = $field['name'].' '.$field['type'];
            if (isset($field['pk']) && $field['pk'] == '1') {
                $meta .= ' PRIMARY KEY';
            }
            if (isset($field['autoincrement']) && $field['autoincrement'] == '1') {
                $meta .= ' AUTOINCREMENT';
            }
            if (isset($field['notnull']) && $field['notnull'] == '1') {
                $meta .= ' NOT NULL';
            }
            if (isset($field['dflt_value'])) {
                $meta .= ' DEFAULT "'.$field['dflt_value'].'"';
            }

            $fieldMeta[] = $meta;
            $newFieldNames[] = '"'.$field['name'].'"';
            if (isset($fields[$field['name']])) {
                $oldFieldNames[] = '"'.$field['name'].'"';
            } else {
                $oldFieldNames[] = 'NULL AS "'.$field['name'].'"';
            }
        }

        $tmpTable = ($tableExist) ? uniqid($collectionName.'_') : $collectionName;
        $sql = 'CREATE TABLE "'.$tmpTable.'" ('."\n".
                '    '.implode(",\n    ", $fieldMeta)."\n".
                ')';
        var_dump($sql);
        exit;
        $this->raw->query($sql);

        if ($tableExist) {
            $sql = 'INSERT INTO "' . $tmpTable . '" (' . implode(', ', $newFieldNames) . ') SELECT '.implode(', ', $oldFieldNames).' FROM "'.$collectionName.'"';
            $this->raw->query($sql);
            $sql = 'DROP TABLE "'.$collectionName.'"';
            $this->raw->query($sql);
            $sql = 'ALTER TABLE "'.$tmpTable.'" RENAME TO "'.$collectionName.'"';
            $this->raw->query($sql);
        }

    }
}