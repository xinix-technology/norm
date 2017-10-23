<?php namespace Norm\Dialect;

use PDO;

use Norm\Model;
use Norm\Cursor;
use Norm\Collection;
use Norm\Dialect\SQLDialect;

class OracleDialect extends SQLDialect
{
    public function grammarInsert($collectionName, $data)
    {

        $fields = array();
        $placeholders = array();

        $fields[0] = 'id';
        $placeholders[] = 'SEQ_'.strtoupper($collectionName).'.NEXTVAL';

        foreach ($data as $key => $value) {
            $fields[] = $this->grammarEscape($key);
            $placeholders[] = ':'.$key;
        }
        
        $sql = 'INSERT INTO ' . $this->grammarEscape($collectionName) . '('.implode(', ', $fields).') VALUES ('.implode(', ', $placeholders).') returning id into :id';

        return $sql;
    }

     public function insert($collectionName, $data)
    {
        $id = 0;
        $sql = $this->grammarInsert($collectionName, $data);

        $statement = $this->raw->prepare($sql);

        foreach ($data as $key => &$value) {
            $statement->bindParam(':'.$key, $value);
        }

        // FIXME Length of params still added manually -> Bug #50906 ORA-03131: an invalid buffer was provided for the next piece(Same as Bug#39820)
        $statement->bindParam(':id', $id, PDO::PARAM_INT,22);
        $statement->execute();

        return $id;
    }
   

    public function grammarUpdate($collectionName, $data)
    {
        $sets = array();

        foreach ($data as $key => $value) {
            $k = $key;
            $sets[] = $this->grammarEscape($k).' = :'.$k;
        }

        $sql = 'UPDATE '.$collectionName.' SET '.implode(', ', $sets) . ' WHERE id = :id';

        return $sql;
    }

    public function update($collectionName, $data)
    {
        $sql = $this->grammarUpdate($collectionName, $data);

        return $this->execute($sql, $data);
    }



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
        $sql = "FROM {$this->grammarEscape($cursor->getCollection()->getName())}";
        $sql = 'SELECT DISTINCT('. $key .') '.$sql;

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
                case 'isnull':
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
                
                $this->expressionCounter = $this->expressionCounter + 1;
                $data['f'.$this->expressionCounter] = $v1;
                $fgroup[] = ':f'.$this->expressionCounter;
            }

            if($operator == 'nin'){
                return $this->grammarEscape($field).' not in ('.implode(', ', $fgroup).')';
            }
            
            return $this->grammarEscape($field).' '.$operator. ' ('.implode(', ', $fgroup).')';
        }elseif($operator == 'isnull'){
            return $this->grammarEscape($field).' is null';

        }

        $this->expressionCounter++;
        $fk = 'f'.$this->expressionCounter;
        $data[$fk] = $fValue;

        return $this->grammarEscape($field).' '.$operator.' :'.$fk;


    }

    public function grammarDistinct(Cursor $cursor,$key){
        $sql = "FROM {$cursor->getCollection()->getName()}";
        $sql = 'SELECT DISTINCT('. $key .') '.$sql;

        return $sql;

    }
    
}
