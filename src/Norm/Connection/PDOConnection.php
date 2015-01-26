<?php

namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\Cursor\PDOCursor;
use Norm\Schema\DateTime;
use Norm\Schema\Object;

class PDOConnection extends \Norm\Connection
{
    protected $DIALECT_MAP = array(
        'mysql' => 'Norm\\Dialect\\MySQLDialect',
        'sqlite' => 'Norm\\Dialect\\SqliteDialect',
    );

    /**
     * Dialect to use for database server
     * @var mixed
     */
    protected $dialect;

    /**
     * @see Norm\Connection::__construct()
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if (!isset($options['prefix'])) {
            throw new \Exception('[Norm\PDOConnection] Missing prefix, check your configuration!');
        }

        if (isset($options['dsn'])) {
            $dsn = $options['dsn'];
        } elseif ($options['prefix'] === 'sqlite') {
            $dsn = 'sqlite:'.$options['database'];
        } else {
            $dsnArray = array();
            foreach ($options as $key => $value) {
                if ($key === 'driver' ||
                    $key === 'prefix' ||
                    $key === 'username' ||
                    $key === 'password' ||
                    $key === 'name' ||
                    $key === 'dialect') {
                    continue;
                }
                $dsnArray[] = "$key=$value";
            }
            $dsn = $options['prefix'].':'.implode(';', $dsnArray);
        }

        if (isset($options['username'])) {
            $this->raw = new \PDO($dsn, $options['username'], $options['password']);
        } else {
            $this->raw = new \PDO($dsn);
        }

        $this->raw->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->raw->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        if (isset($options['dialect'])) {
            $Dialect = $options['dialect'];
        } elseif (isset($this->DIALECT_MAP[$options['prefix']])) {
            $Dialect = $this->DIALECT_MAP[$options['prefix']];
        } else {
            throw new \Exception('[Norm/PDOConnection] Missing dialect!');
            // $Dialect = 'Norm\\Dialect\\SQLDialect';
        }

        $this->dialect = new $Dialect($this);

    }

    /**
     * @see Norm\Connection::persist()
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
                $id = $this->raw->lastInsertId();
            } else {
                throw new \Exception('[Norm/PDOConnection] Insert error.');
            }

            if (!is_null($id)) {
                $marshalledDocument['id'] = $id;
            }
        }

        return $this->unmarshall($marshalledDocument);
    }

    /**
     * @see Norm\Connection::query()
     */
    public function query($collection, array $criteria = null)
    {
        $collection = $this->factory($collection);

        if (!empty($this->options['autocreate'])) {
            $this->dialect->prepareCollection($collection, $criteria);
        }

        return new PDOCursor($collection, $criteria);
    }

    /**
     * @see Norm\Connection::remove()
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
            $statement = $this->raw->prepare($sql);
            $result = $statement->execute();
        } else {
            throw new \Exception('Unimplemented yet!');

            // $sql = "DELETE FROM $collection WHERE id = :id";

            // if ($criteria instanceof \Norm\Model) {
            //     $criteria = $criteria->getId();
            // }

            // if (is_string($criteria)) {
            //     $criteria = array(
            //         '_id' => new \MongoId($criteria),
            //     );
            // } elseif (!is_array($criteria)) {
            //     throw new \Exception('[Norm/Connection] Cannot remove with specified criteria.
            //     Criteria must be array, sring, or model');
            // }

            // $result = $this->raw->$collection->remove($criteria);

            // $statement = $this->getRaw()->prepare($sql);
            // $result = $statement->execute($params);
        }

        return $result;
    }

    /**
     * Getter for dialect
     * @return Norm\Dialect\SQLDialect
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * DDL runner for collection
     * @param  Norm\Collection $collection
     * @return void
     */
    public function ddl(Collection $collection)
    {
        if (!empty($this->options['autoddl'])) {
            $sql = $this->dialect->grammarDDL($collection, $this->options['autoddl']);

            $this->execute($sql);
        }
    }

    protected function execute($sql, array $data = array())
    {
        $statement = $this->raw->prepare($sql);
        return $statement->execute($data);
    }

    // public function listCollections()
    // {
    //     return $this->dialect->listCollections();
    // }

    // public function migrate(Collection $collection) {
    //     if (!$this->hasCollection($collection->name)) {
    //         $grammarCreate = $this->dialect->grammarCreate($collection->name, $collection->schema);

    //         $this->raw->query($grammarCreate);
    //     }
    // }
}
