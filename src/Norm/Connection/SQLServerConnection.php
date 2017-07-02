<?php namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\Schema\DateTime;
use Norm\Schema\Object;
use Norm\Dialect\SQLServerDialect;
use Norm\Cursor\SQLServerCursor;
use Exception;
use PDO;

class SqlServerConnection extends \Norm\Connection
{
    /**
     * Class map of dialect
     *
     * @var array
     */
    
    /**
     * Dialect to use for database server
     *
     * @var mixed
     */
    protected $dialect;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);


        
        if (!isset($options['dsn'])) {
           throw new Exception('[Norm/SQlServerConnection] hostname not found!'); 
        } 

        if (!isset($options['username'])) {
            throw new Exception('[Norm/SQlServerConnection] username not found!');
        }

        

        $dsn = $options['dsn'];
        $this->raw = mssql_connect($dsn,$options['username'],$options['password']);

        if(isset($options['dbname'])){
            mssql_select_db($options['dbname'],$this->raw);
        }   
        

        $this->dialect = new SqlServerDialect($this);
        

    }

    /**
     * {@inheritDoc}
     */
    public function persist($collection, array $document)
    {
        
        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
            $collection = static::factory($collection);
        }

        $this->ddl($collection);

        $marshalledDocument = $this->marshall($document);

        if (isset($document['$id'])) {
            $marshalledDocument['$id'] = $document['$id'];
            $sql = $this->dialect->grammarUpdate($collectionName, $marshalledDocument);
            $marshalledDocument['id'] = $marshalledDocument['$id'];

            unset($marshalledDocument['$id']);

            
                $this->execute($sql, $marshalledDocument);
            
            
        } else {
            $sql = $this->dialect->grammarInsert($collectionName, $marshalledDocument);
            $id = null;

            
            $succeed = $this->execute($sql, $marshalledDocument);    
            

            if ($succeed) {
                $lastInsertId  =  mssql_fetch_array($succeed);
                $id = $lastInsertId[0];
            } else {
                throw new Exception('[Norm/PDOConnection] Insert error.');
            }

            if (!is_null($id)) {
                $marshalledDocument['id'] = $id;
            }
        }

        return $this->unmarshall($marshalledDocument);
    }

    /**
     * {@inheritDoc}
     */
    public function query($collection, array $criteria = null)
    {
        $collection = $this->factory($collection);
        
        return new SQLServerCursor($collection, $criteria);
    }


    public function prepare($sql,$data){

        foreach ($data as $key => $value) {
            $sql = str_replace(":".$key, "'".$value."'", $sql);
        }
        return $sql;


    }

    /**
     * {@inheritDoc}
     */
    public function remove($collection, $criteria = null)
    {
        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
            $collection = static::factory($collection);
        }

        $this->ddl($collection);

        if (func_num_args() === 1) {
            $sql = $this->dialect->grammarDelete($collection);
            $statement = $this->prepare($sql);
            $result = $statement->execute();
        } else {
            $sql = "DELETE FROM $collectionName WHERE id = :id";

            if ($criteria instanceof Model) {
                // $statement = $this->getRaw()->prepare($sql,array(':id' => $criteria->getId()));
                // $result = $statement->execute(array(':id' => $criteria->getId()));
                $result = $this->execute($sql,array(':id' => $criteria->getId()));
            } else {
                throw new Exception('Unimplemented yet!');
            }
        }

        return $result;
    }

    /**
     * Getter for dialect
     *
     * @return \Norm\Dialect\SQLDialect
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * DDL runner for collection
     *
     * @param \Norm\Collection $collection
     *
     * @return void
     */
    public function ddl(Collection $collection)
    {
        if (!empty($this->options['autoddl'])) {
            $sql = $this->dialect->grammarDDL($collection, $this->options['autoddl']);

            $this->execute($sql);
        }
    }

    /**
     * Execute an sql
     *
     * @param string $sql
     * @param array  $data
     *
     * @return bool
     */
    protected function execute($sql, array $data = array())
    {
        try{
            foreach ($data as $key => $value) {
                $sql = str_replace(":".$key, "'".$value."'", $sql);
            }

            $execute = mssql_query($sql,$this->raw);

        }catch(\Exception $e){
            return null;
        }
        

        return $execute;
    }

    /**
     * {@inheritDoc}
     */
    public function marshall($object)
    {
        if (is_array($object)) {

            $result = array();
            foreach ($object as $key => $value) {
                if ($key[0] === '$') {
                    if ($key === '$id' || $key === '$type') {
                        continue;
                    }
                    $result['_'.substr($key, 1)] = $this->marshall($value);
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }
            return $result;
        } elseif ($object instanceof \Norm\Type\DateTime) {
            return $object->format('Y-m-d H:i:s');
        } elseif ($object instanceof \Norm\Type\Collection) {
            return json_encode($object->toArray());
        } elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        } else {
            return $object;
        }
    }
}
