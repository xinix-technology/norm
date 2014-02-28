<?php

namespace Norm\Cursor;

class OCICursor implements ICursor {

    protected $current;

    protected $statement;

	protected $collection;

	protected $dialect;

    protected $criteria;

    protected $raw;

    protected $sortBy;

    protected $limit = 0;

    protected $skip = 0;

    public function __construct($collection) {
        $this->collection = $collection;

        $this->dialect = $collection->connection->getDialect();

        $this->raw = $collection->connection->getRaw();

        $this->criteria = $this->prepareCriteria($collection->criteria);

        $this->row = 0;
    }

    public function current() {
        return $this->current;
    }

    public function getNext() {
        if ($this->valid()) {
            return $this->current();
        }
    }

    public function next() {
        $this->row++;
    }

    public function key() {
        return $this->row;
    }

    public function valid() {
        $stid = $this->getStatement();

        $this->current = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

        $valid = ($this->current !== false);

        return $valid;
    }

    public function rewind() {

    }

    public function prepareCriteria($criteria) {

        if (isset($criteria['$id'])) {
            $criteria['id'] = $criteria['$id'];
            unset($criteria['$id']);
        }

        return $criteria ? : array();
    }

    public function getStatement() {

        if (is_null($this->statement)) {
            $data = array();
            $wheres = array();

            if($this->criteria){
                foreach ($this->criteria as $key => $value) {
                    $wheres[] = $this->dialect->grammarExpression($key, $value, $data);
                }
            }

            $limit = '';
            if ($this->limit > 0) {
                $limit = 'rnum BETWEEN '.($this->skip + 1).' AND '.($this->skip + $this->limit);
            } elseif ($this->skip > 0) {
                $limit = 'rnum > '.$this->skip;
            }

            $select = ($limit !== '') ? 'rownum rnum, ' : '';
            $select .= $this->collection->name.'.*';
            $query = 'SELECT '.$select.' FROM '.$this->collection->name;

            $order = '';
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

            if(!empty($wheres)){
                $query .= ' WHERE '.implode(' AND ', $wheres);
            }

            $query .= $order;


            if ($limit !== '') {
                $query = 'SELECT * FROM ('.$query.') WHERE '.$limit;
            }

            $this->statement = oci_parse($this->raw, $query);

            foreach ($data as $key => $value) {
                oci_bind_by_name($this->statement, ':'.$key, $data[$key]);
            }

            oci_execute($this->statement);
        }

        return $this->statement;
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

}
