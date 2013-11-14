<?php

namespace Norm\Dialect;

class SQLDialect {

    protected $connection;

    protected $raw;

    public function __construct($connection) {
        $this->connection = $connection;
        $this->raw = $connection->getRaw();
    }

    public function listCollections() {
        $statement = $this->raw->query('SHOW TABLES');
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function grammarCreate($name, $schema) {
        $fieldDefinitions = array();
        foreach ($schema as $field) {
            if ($schema instanceof \Norm\Schema\Integer) {
                $fieldDefinitions[] = $field['name'].' INTEGER';
            } elseif ($schema instanceof \Norm\Schema\Text) {
                $fieldDefinitions[] = $field['name'].' TEXT';
            } else {
                $fieldDefinitions[] = $field['name'].' VARCHAR(255)';
            }
        }
        $sql = 'CREATE TABLE '.$name.'(id INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL, '.implode(', ', $fieldDefinitions).')';
        return $sql;
    }
}