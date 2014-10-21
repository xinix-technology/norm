<?php

namespace Norm\Dialect;

use Norm\Collection;
use Norm\Cursor;

abstract class SQLDialect
{
    protected $FIELD_MAP;

    protected $connection;

    protected $raw;

    protected $expressionCounter = 0;

    abstract public function grammarCount(Cursor $cursor, $foundOnly, array &$data = array());

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->raw = $connection->getRaw();
    }

    public function grammarDDL(Collection $collection, $type = 'create')
    {
        switch ($type) {
            case 'create':
                return $this->grammarCreate($collection->getName(), $collection->schema());
            default:
                throw new \Exception(__METHOD__.' with type '.$type.' unimplemented yet');
        }
    }

    public function grammarDelete(Collection $collection, array $criteria = array())
    {
        if (func_num_args() === 1) {
            return "DELETE FROM {$collection->getName()}";
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

        $sql = 'CREATE TABLE IF NOT EXISTS '.$name.'(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';

        if (empty($fieldDefinitions)) {
            $sql .= ')';
        } else {
            $sql .= ', '.implode(', ', $fieldDefinitions).')';
        }

        return $sql;
    }

    public function grammarExpression($key, $value, $collection, &$data)
    {

        if ($key === '!or' || $key === '!and') {
            $wheres = array();
            foreach ($value as $subValues) {

                $subWheres = array();

                foreach ($subValues as $k => $v) {
                    $subWheres[] = $this->grammarExpression($k, $v, $collection, $data);
                }

                switch (count($subWheres)) {
                    case 0:
                        break;
                    case 1:
                        $wheres[] = implode(' AND ', $subWheres);
                        break;
                    default:
                        $wheres[] = '('.implode(' AND ', $subWheres).')';
                        break;
                }
            }

            return '('.implode(' '.strtoupper(substr($key, 1)).' ', $wheres).')';
        }

        $splitted = explode('!', $key, 2);

        $field = $splitted[0];

        $schema = $collection->schema($field);

        if ($field == '$id') {
            $field = 'id';
        } elseif (strlen($field) > 0 && $field[0] === '$') {
            $field = 'h_'.substr($field, 1);
        }

        $operator = '=';
        $multiValue = false;
        $fValue = $value;

        if (isset($splitted[1])) {
            switch ($splitted[1]) {
                case 'like':
                    $operator = 'LIKE';
                    $fValue = "%$value%";
                    break;
                case 'lte':
                    $operator = '<=';
                    break;
                case 'lt':
                    $operator = '<';
                    break;
                case 'gte':
                    $operator = '>=';
                    break;
                case 'gt':
                    $operator = '>';
                    break;
                case 'regex':
                    throw new \Exception('Operator regex is not supported to query.');
                    // return array($field, array('$regex', new \MongoRegex($value)));
                case 'in':
                case 'nin':
                    throw new \Exception('Operator regex is not supported to query.');
                    // $operator = '$'.$splitted[1];
                    // $multiValue = true;
                    // break;
                default:
                    throw new \Exception('Operator regex is not supported to query.');
                    // $operator = '$'.$splitted[1];
                    // break;
            }
        }

        $fk = 'f'.$this->expressionCounter++;
        $data[$fk] = $fValue;

        return "`$field`".' '.$operator.' :'.$fk;

        $op = (isset($key[1])) ? $key[1] : '=';
        switch ($op) {
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

            return "`$key`" . ' ' . $op . ' ('.implode(', ', $fgroup).')';
        } else {
            $this->expressionCounter++;
            $data['f'.$this->expressionCounter] = $value;

            return "`$key`" . ' ' . $op . ' :f' . $this->expressionCounter;
        }
    }

    public function grammarInsert($collectionName, $data)
    {

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

            $fields[] = "`$k`";
            $placeholders[] = ':'.$k;
        }

        $sql = 'INSERT INTO `'.$collectionName.'` ('.implode(', ', $fields).') VALUES ('.implode(', ', $placeholders).')';

        return $sql;
    }

    public function grammarUpdate($collectionName, $data)
    {
        $sets = array();
        foreach ($data as $key => $value) {
            if ($key === '$id') {
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

        $sql = 'UPDATE `'.$collectionName.'` SET '.implode(', ', $sets) . ' WHERE id = :id';

        return $sql;
    }
}
