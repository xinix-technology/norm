<?php

namespace Norm\Dialect;

class SQLDialect {

    protected $connection;

    protected $raw;

    protected $expressionCounter = 0;

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
            if ($field instanceof \Norm\Schema\Integer) {
                $fieldDefinitions[] = $field['name'].' INTEGER';
            } elseif ($field instanceof \Norm\Schema\Text) {
                $fieldDefinitions[] = $field['name'].' TEXT';
            } else {
                $fieldDefinitions[] = $field['name'].' VARCHAR(255)';
            }
        }
        $sql = 'CREATE TABLE '.$name.'(id INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL, '.implode(', ', $fieldDefinitions).')';
        return $sql;
    }

    public function grammarExpression($exp, $value, &$data) {
        $this->expressionCounter = -1;

        $exp = explode('!', $exp);
        $key = $exp[0];
        $op = (isset($exp[1])) ? $exp[1] : '=';
        switch($op) {
            case 'ne':
                $op = '!=';
                break;
            case 'gt':
                $op = '>';
                break;
            case 'gte':
                $op = '>=';
                break;
            case 'lt':
                $op = '<';
                break;
            case 'lt':
                $op = '<=';
                break;
        }

        $this->expressionCounter++;
        $data['f'.$this->expressionCounter] = $value;

        return $key . ' ' . $op . ' :f' . $this->expressionCounter;
    }
}