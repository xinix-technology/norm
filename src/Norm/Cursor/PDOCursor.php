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
class PDOCursor extends Cursor
{
    /**
     * Buffer cache data
     *
     * @var array
     */
    protected $buffer = array();

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

    /**
     * {@inheritDoc}
     */
    public function count($foundOnly = false)
    {
        $data = array();

        $sql = $this->connection->getDialect()->grammarCount($this, $foundOnly, $data);

        $statement = $this->connection->getRaw()->prepare($sql);
        $statement->execute($data);

        $count = $statement->fetch(PDO::FETCH_OBJ)->c;

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
        $current = $this->next[1];

        return isset($current) ? $this->collection->attach($current) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        // Try to get the next element in our data buffer.
        $this->next = each($this->buffer);

        // Past the end of the data buffer
        if (false === $this->next) {
            // Fetch the next row of data
            $row = $this->getStatement()->fetch(PDO::FETCH_ASSOC);

            // Fetch successful
            if ($row) {
                // Add row to data buffer
                $this->buffer[] = $row;
            }

            $this->next = each($this->buffer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->next[0];
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return (false !== $this->next);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        reset($this->buffer);

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
        if (is_null($this->statement)) {
            $this->buffer = array();
            $this->next = false;

            $sql = "SELECT * FROM {$this->connection->getDialect()->grammarEscape($this->collection->getName())}";

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

                    $sorts[] = $this->connection->getDialect()->grammarEscape($key).$op;
                }

                if (!empty($sorts)) {
                    $sql .= ' ORDER BY '.implode(',', $sorts);
                }
            }

            if (isset($this->limit) || isset($this->skip)) {
                $sql .= ' LIMIT '.($this->limit ?: -1).' OFFSET '.($this->skip ?: 0);
            }

            
            $this->statement = $this->connection->getRaw()->prepare($sql);

            $this->statement->execute($data);
        }

        return $this->statement;
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
