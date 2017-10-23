<?php namespace Norm\Dialect;

use Norm\Collection;
use Norm\Cursor;
use Norm\Dialect\SQLDialect;
use Exception;

class SQLServerDialect extends SQLDialect
{
    protected $FIELD_MAP = array(
        'Norm\Schema\Boolean' => 'BOOLEAN',
        'Norm\Schema\Float' => 'DOUBLE',
        'Norm\Schema\Integer' => 'INT',
        'Norm\Schema\Reference' => 'INT',
        'Norm\Schema\DateTime' => 'DATETIME',
        'Norm\Schema\NormArray' => 'VARCHAR(2000)',
        'Norm\Schema\Object' => 'VARCHAR(2000)',
        'Norm\Schema\Text' => 'VARCHAR(2000)',
        'Norm\Schema\String' => 'VARCHAR(255)',
    );

    public function grammarCount(Cursor $cursor, $foundOnly, array &$data = array())
    {
        $sql = "FROM {$this->grammarEscape($cursor->getCollection()->getName())}";

        $wheres = array();

        foreach ($cursor->getCriteria() as $key => $value) {
            $wheres[] = $this->grammarExpression($key, $value, $cursor->getCollection(),$data);
        }

        if (count($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        if ($foundOnly) {
            // $limit = $cursor->limit();
            // $skip = $cursor->skip();
            // if (isset($limit) || isset($skip)) {
            //     $sql .= 'O OFFSET '.($skip ?: 0).' ROWS FETCH NEXT '. ($limit ?: 1000000000) .' ROWS ONLY';
            // }

            $sql = 'SELECT * '.$sql;
            // $sql = 'SELECT * AS c FROM (SELECT * '.$sql.') AS t';
        } else {
            $sql = 'SELECT * '.$sql;
        }

        return $sql;
    }

    public function grammarDistinct(Cursor $cursor,$key){
        $sql = "FROM {$this->grammarEscape($cursor->getCollection()->getName())}";
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

    public function grammarEscape($value){
        return '['.$value.']';
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
            if(!is_array($value)){
                $value = array($value);
            }
            foreach ($value as $k => $v) {
                $v1 = $v;

                if ($v instanceof Model) {
                    $v1 = $v['$id'];
                }

                $this->expressionCounter++;
                $data['f'.$this->expressionCounter] = $v1;
                $fgroup[] = ':f'.$this->expressionCounter;
            }

            return $this->grammarEscape($field).' '.$operator. ' ('.implode(', ', $fgroup).')';
        }

        $this->expressionCounter++
        $fk = 'f'.$this->expressionCounter;
        $data[$fk] = $fValue;

        return $this->grammarEscape($field).' '.$operator.' :'.$fk;

    }


    
}