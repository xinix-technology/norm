<?php namespace Norm\Dialect;

use Norm\Collection;
use Norm\Cursor;
use Exception;
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
            $limit = $cursor->limit();
            $skip = $cursor->skip();
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
        $sql = 'SELECT DISTINCT('. $key .') '.$sql;

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

    //fix by januar : handle for field using  word syntax, just give  charater ` for field
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

            $fields[] = $this->grammarEscape($k);
            $placeholders[] = ':'.$k;
        }

        $sql = 'INSERT INTO '.$this->grammarEscape($collectionName).' (`'.implode('`, `', $fields).'`) VALUES ('.implode(', ', $placeholders).')';
        
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

        $field = trim($splitted[0]);

        $schema = $collection->schema($field);

        if ($field == '$id') {
            $field = 'id';
        } elseif (strlen($field) > 0 && $field[0] === '$') {
            $field = '_'.substr($field, 1);
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
                case 'ne':
                    $operator = '!=';
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
                    throw new Exception('Operator regex is not supported to query.');
                case 'in':
                case 'nin':
                    $operator = $splitted[1];
                break;

                default:
                    throw new Exception('Operator regex is not supported to query.');
            }
        }

        //fix me : change from grammar expresiion old to new grammar expresiion for operator in or nin
        if($operator == 'in' || $operator == 'nin') {
            $fgroup = array();

            foreach ($value as $k => $v) {
                $v1 = $v;

                if ($v instanceof Model) {
                    $v1 = $v['$id'];
                }

                $this->expressionCounter++;
                $data['f'.$this->expressionCounter] = $v1;
                $fgroup[] = ':f'.$this->expressionCounter;
            }

            return '`'.$this->grammarEscape($field).'` '.$operator. ' ('.implode(', ', $fgroup).')';
        }

        $fk = 'f'.$this->expressionCounter++;
        $data[$fk] = $fValue;

        return '`'.$this->grammarEscape($field).'` '.$operator.' :'.$fk;


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
                $sets[] = '`'.$k.'` = :'.$k;

                unset($record[$key]);
            } else {
                $k = $key;
                $sets[] = '`'.$k.'` = :'.$k;
            }
        }

        $sql = 'UPDATE '.$this->grammarEscape($collectionName).' SET '.implode(', ', $sets) . ' WHERE id = :id';

        return $sql;
    }
}
