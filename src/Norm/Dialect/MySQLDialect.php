<?php namespace Norm\Dialect;

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
}
