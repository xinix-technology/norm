<?php

namespace Norm\Dialect;

class OracleDialect extends SQLDialect {

	public function grammarInsert($collectionName, $data){

        $fields = array();
        $placeholders = array();

        $fields[0] = 'id';
	    $placeholders[] = 'SEQ_'.strtoupper($collectionName).'.NEXTVAL';
        
        foreach ($data as $key => $value) {
            $fields[] = $key;
            $placeholders[] = ':'.$key;
        }

        $sql = 'INSERT INTO ' . $collectionName . '('.implode(', ', $fields).') VALUES ('.implode(', ', $placeholders).') returning id into :id';
        return $sql;
    }

    public function insert($collectionName, $data){
        $id = 0;
        $sql = $this->grammarInsert($collectionName, $data);
        
        $statement = $this->raw->prepare($sql);
        foreach ($data as $key => &$value) {
        	$statement->bindParam(':'.$key, $value);
        }

        // FIXME Length of params still added manually -> Bug #50906 ORA-03131: an invalid buffer was provided for the next piece(Same as Bug#39820) 
        $statement->bindParam(':id',$id,\PDO::PARAM_INT,22);
        $statement->execute();

        return $id;
    }

    public function grammarUpdate($collectionName, $data){

        $sets = array();
        foreach ($data as $key => $value) {
            $k = $key;
            $sets[] = $k.' = :'.$k;
        }

        $sql = 'UPDATE '.$collectionName.' SET '.implode(', ', $sets) . ' WHERE id = :id';
        return $sql;
    }

    public function update($collectionName, $data){
        $sql = $this->grammarUpdate($collectionName, $data);
        return $this->execute($sql, $data);
    }

}












