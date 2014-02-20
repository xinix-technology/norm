<?php

namespace Norm\Cursor;

class OCICursor implements \Iterator {

	protected $current;

	protected $statement;

	protected $collection;

	protected $dialect;

	protected $criteria;

	protected $raw;

	public function __construct($collection) {
		$this->collection = $collection;

		$this->dialect = $collection->connection->getDialect();

		$this->raw = $collection->connection->getRaw();

		$this->criteria = $this->prepareCriteria($collection->criteria);
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
    	throw new \Exception("Not implemented yet");
    }

    public function key() {
    	throw new \Exception("Not implemented yet");
    }

    public function valid() {
    	$stid = $this->getStatement();

    	$this->current = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_LOBS + OCI_RETURN_NULLS);

        $valid = ($this->current !== false);

        $this->current = array_change_key_case($this->current, CASE_LOWER);

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
    		$query = 'SELECT * FROM '.$this->collection->name;

    		if($this->criteria){
    			$data = array();
    			$wheres = array();
    			foreach ($this->criteria as $key => $value) {
    				$wheres[] = $this->dialect->grammarExpression($key, $value, $data);
    			}
    			if (!empty($wheres)) {
    				$query .= ' WHERE '.implode(' AND ', $wheres);
    			}
    		}

    		$this->statement = oci_parse($this->raw, $query);

			foreach ($data as $key => $value) {
				oci_bind_by_name($this->statement, ':'.$key, $data[$key]);
			}

			oci_execute($this->statement);
    	}

    	return $this->statement;
    }

}
