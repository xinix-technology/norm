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

    public function prepareCollection($name) {
        throw new \Exception('Unimplemented yet!');
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


        if ($op == 'in') {
            $fgroup = array();
            foreach ($value as $k => $v) {
                $v1 = $v;
                if ($v instanceof \Norm\Model) {
                    $v1 = $v['$id'];
                }
                $this->expressionCounter++;
                $data['f'.$this->expressionCounter] = $v1;
                $fgroup[] = ':f'.$this->expressionCounter;
            }
            if (empty($fgroup)) {
                return '(1)';
            }
            return $key . ' ' . $op . ' ('.implode(', ', $fgroup).')';
        } else {
            $this->expressionCounter++;
            $data['f'.$this->expressionCounter] = $value;
            return $key . ' ' . $op . ' :f' . $this->expressionCounter;
        }
    }

    public function execute($sql, $data){
        $statement = $this->raw->prepare($sql);
        $result = $statement->execute($data);
        return $result;
    }

    public function grammarInsert($collectionName, $data){

        $fields = array();
        $placeholders = array();

        foreach ($data as $key => $value) {
            if ($key === '$id') {
                continue;
            }

            if ($key[0] === '$') {
                $k = '_'.substr($key, 1);
                $data[$k] = $value;
                unset($data[$key]);
            } else {
                $k = $key;
                $sets[] = $k.' = :'.$k;
            }

            $fields[] = $k;
            $placeholders[] = ':'.$k;
        }

        $sql = 'INSERT INTO ' . $collectionName . '('.implode(', ', $fields).') VALUES ('.implode(', ', $placeholders).')';
        return $sql;
    }

    public function insert($collectionName, $data){
        $sql = $this->grammarInsert($collectionName, $data);
        return $this->execute($sql, $data);
    }

    public function grammarUpdate($collectionName, $data){

        $sets = array();
        foreach ($data as $key => $value) {
            if ($key === '$id') {
                $data['id'] = $value;
                unset($data['$id']);
                continue;
            }

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
        return $sql;
    }

    public function update($collectionName, $data){
        $sql = $this->grammarUpdate($collectionName, $data);

        foreach ($data as $key => $value) {
            if ($key === '$id') {
                $data['id'] = $value;
                unset($data['$id']);
            }
        }
        
        return $this->execute($sql, $data);
    }
}

























