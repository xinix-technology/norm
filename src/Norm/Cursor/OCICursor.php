<?php

namespace Norm\Cursor;

class OCICursor implements \Iterator {

	protected $current;

	protected $statement;

	protected $collection;
	
	protected $dialect;

	protected $criteria;

	protected $raw;

    protected $sortBy;

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
    		$query = 'SELECT * FROM '.$this->collection->name;
    		
    		$data = array();
            if($this->criteria){
    			$wheres = array();
    			foreach ($this->criteria as $key => $value) {
    				$wheres[] = $this->dialect->grammarExpression($key, $value, $data);
    			}
    			if (!empty($wheres)) {
    				$query .= ' WHERE '.implode(' AND ', $wheres);
    			}
    		}

            if($this->sortBy){
                $order = array();
                foreach ($this->sortBy as $key => $value) {
                    if($value == 1){
                        $op = ' ASC';
                    } else {
                        $op = ' DESC';
                    }
                    $order[] = $key . $op;
                }
                if(!empty($order)){
                    $query .= ' ORDER BY '.implode(',', $order);
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

    public function sort(array $fields) {
        $this->sortBy = $fields;
        return $this;
    }

}
