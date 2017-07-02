<?php namespace Norm\Cursor;

use Norm\Norm;
use Norm\Schema\Reference;
use Norm\Cursor;

/**
 * Oracle OCI Cursor.
 *
 * @author    Januar Siregar <januar.siregar@gmail.com>
 * @copyright 2017 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class OCICursor extends Cursor
{
    /**
     * Collection implementation.
     *
     * @var \Norm\Collection
     */
    protected $collection;

    /**
     * Dialect implementation.
     *
     * @var \Norm\Dialect\OracleDialect
     */
    protected $dialect;

    /**
     * Criteria of current query
     *
     * @var array
     */
    

    /**
     * Raw query
     *
     * @var string
     */
    protected $raw;

    /**
     * Sorter of current query.
     *
     * @var array
     */
    protected $sortBy;

    /**
     * Limit document that will be fetched by `n`.
     *
     * @var integer
     */
    protected $limit = 0;

    /**
     * Skip document that will be fetched by `n`.
     *
     * @var integer
     */
    protected $skip = 0;

    /**
     * Match criteria of current query.
     *
     * @var array
     */
    protected $match;

    /**
     * Rows of current document.
     *
     * @var array
     */
    protected $rows = null;

    /**
     * Index of current active document index.
     *
     * @var integer
     */
    protected $index = -1;

    /**
     * {@inheritDoc}
     */
    public function __construct($collection, $criteria = array())
    {
        parent::__construct($collection,$criteria);

        $this->collection = $collection;

        $this->dialect = $this->connection->getDialect();
        
        $this->raw = $this->connection->getRaw();
        
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        if ($this->valid()) {
            // return $this->rows[$this->index];
            return $this->collection->attach($this->rows[$this->index]);
        }
    }

    
    /**
     * {@inheritDoc}
     */
    public function next()
    {
        if (is_null($this->rows)) {
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
     * {@inheritDoc}
     */
    public function count($foundOnly = false)
    {
        $wheres   = array();
        $data     = array();
        $matchOrs = array();
        $criteria = $this->translateCriteria($this->criteria);

        if (is_null($this->match)) {
            $criteria = $this->translateCriteria($this->criteria);
            if ($criteria) {
                foreach ($criteria as $key => $value) {
                    $wheres[] = $this->dialect->grammarExpression($key, $value,$this->collection, $data);
                }
            }
        } else {
            $schema = $this->collection->schema();

            $i = 0;

            foreach ($schema as $key => $value) {
                if ($value instanceof Reference) {
                    $foreign = $value['foreign'];
                    $foreignLabel = $value['foreignLabel'];
                    $foreignKey = $value['foreignKey'];
                    $matchOrs[] = $this->getQueryReference($key, $foreign, $foreignLabel, $foreignKey, $i);
                } else {
                    $matchOrs[] = $key.' LIKE :f'.$i;
                    $i++;
                }
            }

            $wheres[] = '('.implode(' OR ', $matchOrs).')';
        }

        $query = "SELECT count(ROWNUM) r FROM " . $this->collection->getName();

        
        if (!empty($wheres)) {
            $query .= ' WHERE '.implode(' AND ', $wheres);
        }
        
        
        $statement = oci_parse($this->raw, $query);

        foreach ($data as $key => $value) {
                oci_bind_by_name($statement, ':'.$key, $data[$key]);
        }

        if ($foundOnly) {
            if ($matchOrs) {
                $match = '%'.$this->match.'%';

                foreach ($matchOrs as $key => $value) {
                    oci_bind_by_name($statement, ':f'.$key, $match);
                }
            }
        }

        oci_execute($statement);

        $result = array();

        while ($row = oci_fetch_array($statement, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $result[] = $row;
        }

        oci_free_statement($statement);

        $r = reset($result);
        $r = $r['R'];

        return (int) $r;
    }

    /**
     * {@inheritDoc}
     */
    public function match($q)
    {
        $this->match = $q;

        return $this;
    }

    /**
     * {@inheritDoc}
     */

    // comment for other criteria 
    public function prepareCriteria($criteria)
    {
        if (is_null($criteria)) {
            $criteria = array();
        }

        if (array_key_exists('$id', $criteria)) {
            $criteria['id'] = $criteria['$id'];
            unset($criteria['$id']);
        }

        return $criteria ? : array();
    }


    public function translateCriteria(array $criteria = array())
    {
        if (isset($criteria['$id'])) {
            $criteria['id'] = $criteria['$id'];

            unset($criteria['$id']);
        }

        return $criteria;
    }


    public function distinct($key){

    }

    /**
     * Execute a query.
     *
     * @return int
     */
    public function getStatement($type = null)
    {
        $data = array();
        $wheres = array();
        $matchOrs = array();

        if (is_null($this->match)) {
            $criteria = $this->translateCriteria($this->criteria);
            
            if ($criteria) {
                foreach ($criteria as $key => $value) {
                    $wheres[] = $this->dialect->grammarExpression($key, $value,$this->collection, $data);
                }
            }
        } else {
            $schema = $this->collection->schema();

            $i = 0;

            foreach ($schema as $key => $value) {
                if ($value instanceof \Norm\Schema\Reference) {
                    $foreign = $value['foreign'];
                    $foreignLabel = $value['foreignLabel'];
                    $foreignKey = $value['foreignKey'];
                    $matchOrs[] = $this->getQueryReference($key, $foreign, $foreignLabel, $foreignKey, $i);
                } else {
                    $matchOrs[] = $key.' LIKE :f'.$i;
                    $i++;
                }
            }

            $wheres[] = '('.implode(' OR ', $matchOrs).')';
        }

        $select  = '';

        if ($this->skip > 0 or $this->limit > 0) {
            $select .= 'rownum r, ';
        }

        $select  .= $this->collection->getName().'.*';
        $query   = 'SELECT '.$select.' FROM '.$this->collection->getName();
        $order   = '';

        if ($this->sortBy) {
            foreach ($this->sortBy as $key => $value) {
                if ($value == 1) {
                    $op = ' ASC';
                } else {
                    $op = ' DESC';
                }
                $order[] = $key . $op;
            }

            if (!empty($order)) {
                $order = ' ORDER BY '.implode(',', $order);
            }
        }

        if (!empty($wheres)) {
            $query .= ' WHERE '.implode(' AND ', $wheres);
        }

        $limit = '';

        if ($this->skip > 0) {
            $limit = 'r > '.($this->skip).' AND ROWNUM <= (SELECT COUNT(ROWNUM) FROM ('.$query.'))';

            if ($this->limit > 0) {
                $limit = 'r > '.($this->skip).' AND ROWNUM <= ' . $this->limit;
            }
        } elseif ($this->limit > 0) {
            $limit = 'r <= ' . $this->limit;
        }

        $query .= $order;

        if ($limit !== '') {
            $query = 'SELECT * FROM ('.$query.') WHERE '.$limit;
        }
        
        $statement = oci_parse($this->raw, $query);

        foreach ($data as $key => $value) {
            oci_bind_by_name($statement, ':'.$key, $data[$key]);
        }

        if ($matchOrs) {
            
            $match = '%'.$this->match.'%';

            foreach ($matchOrs as $key => $value) {
                oci_bind_by_name($statement, ':f'.$key, $match);
            }
        }
        
        oci_execute($statement);

        $result = array();

        while ($row = oci_fetch_array($statement, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $result[] = $row;
        }


        $this->rows = $result;

        oci_free_statement($statement);

        $this->index = -1;

    }

    /**
     * {@inheritDoc}
     */
    
     public function sort(array $sorts = array())
    {
        if (func_num_args() === 0) {
            return $this->sorts;
        }

        $this->sorts = array();
        
        foreach ($sorts as $key => $value) {
            if ($key[0] === '$') {
                
                $key = 'h_' . substr($key, 1);
            }

            $this->sorts[$key] = $value;
        }
        
        $this->sortBy = $this->sorts;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function limit($num =0)
    {
        $this->limit = $num;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function skip($offset = 0)
    {
        $this->skip = $offset;

        return $this;
    }

    /**
     * Find reference of a foreign key.
     *
     * @param string $key
     * @param string $foreign
     * @param string $foreignLabel
     * @param string $foreignKey
     * @param int &$i
     *
     * @return string
     */
    public function getQueryReference($key = '', $foreign = '', $foreignLabel = '', $foreignKey = '', &$i)
    {
        $model      = Norm::factory($foreign);
        $refSchemes = $model->schema();
        $foreignKey = $foreignKey ?: 'id';

        if ($foreignKey == '$id') {
            $foreignKey = 'id';
        }

        $query = $key . ' IN (SELECT '.$foreignKey.' FROM '.strtolower($foreign).' WHERE '.$foreignLabel.' LIKE :f'.$i.') ';
        $i++;

        return $query;
    }
}
