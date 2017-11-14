<?php namespace Norm\Connection;

use Norm\Collection;
use Norm\Model;
use Norm\Cursor\PDOCursor;
use Norm\Type\DateTime;
use Norm\Type\NDateTime; 
use Norm\Type\NDate;
use Norm\Schema\Object;
use Exception;
use PDO;

class PDOConnection extends \Norm\Connection
{
    /**
     * Class map of dialect
     *
     * @var array
     */
    protected $DIALECT_MAP = array(
        'mysql' => 'Norm\\Dialect\\MySQLDialect',
        'sqlite' => 'Norm\\Dialect\\SqliteDialect',
    );

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

        if (!isset($options['prefix'])) {
            throw new Exception('[Norm\PDOConnection] Missing prefix, check your configuration!');
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
            $this->raw = new PDO($dsn, $options['username'], $options['password']);
        } else {
            $this->raw = new PDO($dsn);
        }

        $this->raw->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->raw->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        if (isset($options['dialect'])) {
            $Dialect = $options['dialect'];
        } elseif (isset($this->DIALECT_MAP[$options['prefix']])) {
            $Dialect = $this->DIALECT_MAP[$options['prefix']];
        } else {
            throw new Exception('[Norm/PDOConnection] Missing dialect!');
        }

        $this->dialect = new $Dialect($this);

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
                $id = $this->raw->lastInsertId();
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

        if (!empty($this->options['autocreate'])) {
            $this->dialect->prepareCollection($collection, $criteria);
        }

        return new PDOCursor($collection, $criteria);
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
            $statement = $this->raw->prepare($sql);
            $result = $statement->execute();
        } else {
            $sql = "DELETE FROM $collectionName WHERE id = :id";

            if ($criteria instanceof Model) {
                $statement = $this->getRaw()->prepare($sql);
                $result = $statement->execute(array(':id' => $criteria->getId()));
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
        $statement = $this->raw->prepare($sql);

        return $statement->execute($data);
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
        } elseif ($object instanceof NDateTime || $object instanceof DateTime) {
            return $object->format('Y-m-d H:i:s');
        } elseif ($object instanceof NDate) {
            return $object->format('Y-m-d');
        } elseif ($object instanceof \Norm\Type\Collection) {
            return json_encode($object->toArray());
        } elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        } else {
            return $object;
        }
    }
}
