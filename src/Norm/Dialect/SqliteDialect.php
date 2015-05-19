<?php namespace Norm\Dialect;

use Exception;
use Norm\Cursor;

class SqliteDialect extends SQLDialect
{
    protected $FIELD_MAP = array(
        'Norm\Schema\Boolean' => 'BOOL',
        'Norm\Schema\Float' => 'DOUBLE',
        'Norm\Schema\Integer' => 'INTEGER',
        'Norm\Schema\Reference' => 'INTEGER',
        'Norm\Schema\DateTime' => 'DATETIME',
        'Norm\Schema\NormArray' => 'TEXT',
        'Norm\Schema\Object' => 'TEXT',
        'Norm\Schema\Text' => 'TEXT',
        'Norm\Schema\String' => 'VARCHAR',
    );

    public function grammarCount(Cursor $cursor, $foundOnly, array &$data = array())
    {
        $sql = "FROM {$cursor->getCollection()->getName()}";

        $wheres = array();

        foreach ($cursor->getCriteria() as $key => $value) {
            $wheres[] = $this->collection->connection->getDialect()->grammarExpression($key, $value, $data);
        }

        if (count($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        if ($foundOnly) {
            $sorts = $cursor->sort();
            $limit = $cursor->limit();
            $skip = $cursor->skip();

            if (isset($sorts)) {
                throw new Exception(__FILE__.':'.__LINE__.' unimplemented yet!');
            }

            if (isset($limit) || isset($skip)) {
                $sql .= ' LIMIT '.($limit ?: -1).' OFFSET '.($skip ?: 0);
            }

            $sql = 'SELECT COUNT(*) AS c FROM (SELECT * '.$sql.')';
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

}
