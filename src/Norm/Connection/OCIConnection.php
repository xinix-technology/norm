<?php
 namespace Norm\Connection;

use Exception;
use Norm\Model;
use Norm\Collection;
use Norm\Connection;
use Norm\Dialect\OracleDialect;
use Norm\Cursor\OCICursor as Cursor;
use Norm\Type\DateTime as NDateTime;
use Norm\Type\NDate; 

/**
 * OCI Connection.
 *
 * @author    Januar Siregar <januar.siregar@gmail.com>
 * @copyright 2017 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class OCIConnection extends Connection
{
    protected $dialect;

    /**
     * Initializing class
     *
     * @param array $options
     *
     * @return void
     */

    public function __construct(array $options = array()){
        
        $this->initialize($options);
    }

    


    public function initialize($options = array())
    {
        $defaultOptions = array(
            'username' => null,
            'password' => null,
            'dbname' => null,
            'charset' => null,
            'mode' => null
        );

        $this->options = array_merge($defaultOptions, $options);


        $this->raw = oci_connect(
            $this->options['username'],
            $this->options['password'],
            $this->options['dbname'],
            $this->options['charset'],
            $this->options['mode']
        );

        $this->prepareInit();

        $this->dialect = new OracleDialect($this);
    }

    /**
     * Preparing initialization of connection
     *
     * @return void
     */
    protected function prepareInit()
    {
        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        oci_execute($stid);
        oci_free_statement($stid);

        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_SORT = BINARY_CI");
        oci_execute($stid);
        oci_free_statement($stid);

        $stid = oci_parse($this->raw, "ALTER SESSION SET NLS_COMP = LINGUISTIC");
        oci_execute($stid);
        oci_free_statement($stid);
    }

    /**
     * {@inheritDoc}
     */
    public function query($collection, array $criteria = array())
    {   $collection = $this->factory($collection);
        
        return new Cursor($collection,$criteria);
    }

    /**
     * Sync data to database. If it's new data, we insert it as new document, otherwise, if the document exists, we just update it.
     *
     * @param Collection $collection
     * @param Model $model
     *
     * @return bool
     */
    public function persist($collection, array $document)
    {
        

        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
            $collection = static::factory($collection);
        }
        
        $data = $this->marshall($document);
        $result = false;

        
        if (!isset($document['$id'])) {
            $id = $this->insert($collectionName, $data);
            if ($id) {
                $data['$id'] = $id;
                $result = $data;
            }
        } else {
            $data['id'] = $document['$id'];
            unset($data['$id']);
            $result = $this->update($collectionName, $data);

            if ($result) {
                $result = $data;
            }
        }

        return $this->unmarshall($result);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($collection,$criteria = null)
    {
        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
            $collection = static::factory($collection);
        }

        if($criteria instanceof Model){
            $id = $criteria->getId();

            $sql = 'DELETE FROM '.$collectionName.' WHERE id = :id';

            $stid = oci_parse($this->raw, $sql);
            oci_bind_by_name($stid, ":id", $id);
            $result = oci_execute($stid);
            oci_free_statement($stid);
        }else{
            throw new Exception('Unimplemented yet!');
        }
        
        return $result;
    }

    /**
     * Perform insert new document to database.
     *
     * @param string $collectionName
     * @param mixed $data
     *
     * @return bool
     */
    public function insert($collectionName, $data)
    {
        $id = 0;
        $sql = $this->dialect->grammarInsert($collectionName, $data);

        $stid = oci_parse($this->raw, $sql);

        //Fixme : problem from other oracle dialect on method insert
        oci_bind_by_name($stid, ":id", $id,-1,SQLT_INT);

        foreach ($data as $key => $value) {
            oci_bind_by_name($stid, ":".$key, $data[$key]);
        }

        oci_execute($stid);

        oci_free_statement($stid);

        return $id;
    }

    /**
     * Perform update to a document.
     *
     * @param string $collectionName
     * @param mixed $data
     *
     * @return bool
     */
    public function update($collectionName, $data)
    {
        $sql = $this->dialect->grammarUpdate($collectionName, $data);

        $stid = oci_parse($this->raw, $sql);

        oci_bind_by_name($stid, ":id", $data['id']);

        foreach ($data as $key => $value) {
            oci_bind_by_name($stid, ":".$key, $data[$key]);
        }

        $result = oci_execute($stid);

        oci_free_statement($stid);

        return $result;
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
                    } else {
                        $result['h_'.substr($key, 1)] = $this->marshall($value);
                    }
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }
            return $result;
        }else  if ($object instanceof NDateTime) {
            return $object->format('Y-m-d H:i:s');
        }else  if ($object instanceof NDate) {
            return $object->format('Y-m-d');
        }elseif ($object instanceof \Norm\Type\Collection) {
            return json_encode($object->toArray());
        }elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        } else {
            return $object;
        }
    }



     public function unmarshall($object)
    {

        if($object instanceof \Norm\Model){
            return $object;
        }
        
        $newobject = array();
        if (isset($object['ID'])) {
            $newobject['$id'] = $object['ID'];
            
        }

        foreach ($object as $key => $value) {
            if($key === 'R' || $key === 'ID'){
                continue;
            }
            $key = strtolower($key);

            if ($key[0].$key[1] === 'h_') {
                $newobject['$'.substr($key,2)] = $value;
            } else {
                $newobject[$key] = $value;
            }
        }

        return $newobject;
    }

    /**
     * Get dialect used by this implementation.
     *
     * @return \Norm\Dialect\OracleDialect
     */
    public function getDialect()
    {
        return $this->dialect;
    }
}
