<?php

namespace Norm\Cursor;

use Norm\Norm;

class OCICursor extends \Norm\Cursor implements ICursor {

    protected $collection;

    protected $dialect;

    protected $criteria;

    protected $raw;

    protected $sortBy;

    protected $limit = 0;

    protected $skip = 0;

    protected $match;

    protected $rows = NULL;

    protected $index = -1;

    public function __construct($collection) {
        $this->collection = $collection;

        $this->dialect = $collection->connection->getDialect();

        $this->raw = $collection->connection->getRaw();

        $this->criteria = $collection->criteria;
    }

    public function current() {
        if ($this->valid()) {
            return $this->rows[$this->index];
        }
    }

    public function getNext() {
        $this->next();
        return $this->current();
    }

    public function next() {

        if (is_null($this->rows)) {
            $this->execute();
        }

        $this->index++;
    }

    public function key() {
        return $this->index;
    }

    public function valid() {
        return isset($this->rows[$this->index]);
    }

    public function rewind() {
        $this->index = -1;

        $this->next();

    }

    // FIXME: krisanalfa Make a separate function to build where, matchOr, skip, limit, and order
    public function count($foundOnly = false) {
        $wheres   = array();
        $data     = array();
        $matchOrs = array();
        $criteria = $this->prepareCriteria($this->criteria);

        if (is_null($this->match)) {
            $criteria = $this->prepareCriteria($this->criteria);
            if($criteria) {
                foreach ($criteria as $key => $value) {
                    $wheres[] = $this->dialect->grammarExpression($key, $value, $data);
                }
            }
        } else {
            $schema = $this->collection->schema();

            $i = 0;
            foreach ($schema as $key => $value) {
                if($value instanceof \Norm\Schema\Reference){
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

        $query = "SELECT count(ROWNUM) r FROM " . $this->collection->name;

        if ($foundOnly) {
            if(!empty($wheres)) {
                $query .= ' WHERE '.implode(' AND ', $wheres);
            }
        }

        $statement = oci_parse($this->raw, $query);

        foreach ($data as $key => $value) {
            oci_bind_by_name($statement, ':'.$key, $data[$key]);
        }

        if($foundOnly) {
            if ($matchOrs) {
                $match = '%'.$this->match.'%';

                foreach ($matchOrs as $key => $value) {
                    oci_bind_by_name($statement, ':f'.$key, $match);
                }
            }
        }

        oci_execute($statement);

        $result = array();
        while($row = oci_fetch_array($statement, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $result[] = $row;
        }

        oci_free_statement($statement);

        $r = reset($result);
        $r = $r['R'];

        return (int) $r;
    }

    public function match($q) {
        $this->match = $q;
        return $this;
    }

    public function prepareCriteria($criteria) {
        if(is_null($criteria)){
            $criteria = array();
        }
        
        if (array_key_exists('$id', $criteria)) {
            $criteria['id'] = $criteria['$id'];
            unset($criteria['$id']);
        }

        
        return $criteria ? : array();
    }

    // FIXME: krisanalfa Make a separate function to build where, matchOr, skip, limit, and order
    public function execute() {

        $data = array();
        $wheres = array();
        $matchOrs = array();

        if (is_null($this->match)) {
            $criteria = $this->prepareCriteria($this->criteria);
            if($criteria) {
                foreach ($criteria as $key => $value) {
                    $wheres[] = $this->dialect->grammarExpression($key, $value, $data);
                }
            }
        } else {
            $schema = $this->collection->schema();

            $i = 0;
            foreach ($schema as $key => $value) {
                if($value instanceof \Norm\Schema\Reference){
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

        $select  .= $this->collection->name.'.*';
        $query   = 'SELECT '.$select.' FROM '.$this->collection->name;
        $order   = '';

        if($this->sortBy){
            foreach ($this->sortBy as $key => $value) {
                if($value == 1){
                    $op = ' ASC';
                } else {
                    $op = ' DESC';
                }
                $order[] = $key . $op;
            }
            if(!empty($order)){
                $order = ' ORDER BY '.implode(',', $order);
            }
        }

        if(!empty($wheres)) {
            $query .= ' WHERE '.implode(' AND ', $wheres);
        }

        $limit = '';

        if ($this->skip > 0) {
            $limit = 'r > '.($this->skip).' AND ROWNUM <= (SELECT COUNT(ROWNUM) FROM ('.$query.'))';

            if ($this->limit > 0) {
                $limit = 'r > '.($this->skip).' AND ROWNUM <= ' . $this->limit;
            }
        } else if($this->limit > 0) {
            $limit = 'ROWNUM <= ' . $this->limit;
        }

        $query .= $order;

        if ($limit !== '') {
            $query = 'SELECT * FROM ('.$query.') WHERE '.$limit;
        }

        $statement = oci_parse($this->raw, $query);

        foreach ($data as $key => $value) {
            oci_bind_by_name($statement, ':'.$key, $data[$key]);
        }

        if($matchOrs) {
            $match = '%'.$this->match.'%';

            foreach ($matchOrs as $key => $value) {
                oci_bind_by_name($statement, ':f'.$key, $match);
            }
        }

        oci_execute($statement);

        $result = array();
        while($row = oci_fetch_array($statement, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS)) {
            $result[] = $row;
        }
        $this->rows = $result;

        oci_free_statement($statement);

        $this->index = -1;
    }

    public function sort(array $fields) {
        $this->sortBy = $fields;
        return $this;
    }

    public function limit($num){
        $this->limit = $num;
        return $this;
    }

    public function skip($offset) {
        $this->skip = $offset;
        return $this;
    }

    public function getQueryReference($key = '', $foreign = '', $foreignLabel = '', $foreignKey = '', &$i){
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

