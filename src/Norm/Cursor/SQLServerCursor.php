<?php namespace Norm\Cursor;

use PDO;
use Norm\Cursor;
use Norm\Collection;

/**
 * Oracle OCI Cursor.
 *
 * @author    Aprianto Pramana Putra <apriantopramanaputra@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class SQLServerCursor extends Cursor
{
    /**
     * Buffer cache data
     *
     * @var array
     */
    protected $buffer = array();
    protected $rows = null;

    /**
     * Next record read
     *
     * @var boolean
     */
    protected $next = false;

    /**
     * PDO Statement
     *
     * @var \PDOStatement
     */
    protected $statement;

    protected $index;

    /**
     * {@inheritDoc}
     */
    public function count($foundOnly = false)
    {
        $data = array();

        $sql = $this->connection->getDialect()->grammarCount($this, $foundOnly, $data);

        $statement = $this->connection->prepare($sql,$data);
        
        $count_data = mssql_query($statement,$this->connection->getRaw());

        $count = mssql_num_rows($count_data);

        mssql_free_result($count_data);

        return intval($count);
    }

    /**
     * {@inheritDoc}
     */
    public function translateCriteria(array $criteria = array())
    {
        if (isset($criteria['$id'])) {
            $criteria['id'] = $criteria['$id'];

            unset($criteria['$id']);
        }

        return $criteria;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->collection->attach($this->rows[$this->index]);
        }
    }

     public function getNext()
        {
            $this->next();

            return $this->current();
        }

    /**
     * {@inheritDoc}
     */
    public function next()
    {

        if(is_null($this->rows)){
                $this->getStatement();
        }

        $this->index++;
       
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return isset($this->rows[$this->index]);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {   

        $this->index = -1;

        $this->next();
    }

    /**
     * Get query statement of current cursor.
     *
     * @param mixed $type
     *
     * @return \PDOStatement
     */
    public function getStatement($type = null)
    {
        
        // $this->buffer = array();
        // $this->next = false;

        $sql = "SELECT * FROM [{$this->collection->getName()}]";

        $wheres = array();
        $data = array();

        foreach ($this->criteria as $key => $value) {
            $wheres[] = $a= $this->connection->getDialect()->grammarExpression(
                $key,
                $value,
                $this->collection,
                $data
            );
        }

        if (!empty($wheres)) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }

        if (isset($this->sorts)) {
            $sorts = array();

            foreach ($this->sorts as $key => $value) {
                if ($value == 1) {
                    $op = ' ASC';
                } else {
                    $op = ' DESC';
                }

                $sorts[] = $key.$op;
            }

            if (!empty($sorts)) {
                $sql .= ' ORDER BY '.implode(',', $sorts);
            }
        }else{
            $sql .= ' ORDER BY ID';

        }

        if (isset($this->limit) || isset($this->skip)) {
            $sql .= ' OFFSET '.($this->skip ?: 0).' ROWS FETCH NEXT '. ($this->limit ?: -1) .' ROWS ONLY';
            // $sql .= ' LIMIT '.($this->limit ?: -1).' OFFSET '.($this->skip ?: 0);
        }

        $query = $this->connection->prepare($sql,$data);
        
        $statement = mssql_query($query);    

        $this->rows = array();

        while ($row  = mssql_fetch_assoc($statement)) {
            $this->rows[] = $row;
        }
        

        mssql_free_result($statement);
        
        $this->index = -1;

        
    }

    /**
     * {@inheritDoc}
     */
    public function distinct($key)
    {
        $sql = $this->connection->getDialect()->grammarDistinct($this,$key);
        $statement = $this->connection->getRaw()->prepare($sql);
        $statement->execute(array());

        $result = array();
        while($row = $statement->fetch(\PDO::FETCH_ASSOC)){
            $result[] = $row[$key];
        }
        return $result;

    }
}
