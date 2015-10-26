<?php
namespace Norm\Adapter;

use MongoId;
use Exception;
use MongoDate;
use MongoClient;
use Norm\Connection;
use Norm\Cursor;
use ROH\Util\Options;
use ROH\Util\Collection;
use Norm\Type\DateTime as NormDateTime;

class Mongo extends Connection
{
    protected $client;

    public function __construct($options = array())
    {
        $options = Options::create([
            'hostname' => MongoClient::DEFAULT_HOST,
            'port' => MongoClient::DEFAULT_PORT,
        ])->merge($options);

        if (!isset($options['database'])) {
            throw new InvalidArgumentException('Missing database name for Mongo connection, '.
                'please check your configuration');
        }

        parent::__construct($options);
    }

    public function getRaw()
    {
        if (is_null($this->raw)) {
            $prefix = '';
            if (isset($this->options['username'])) {
                $prefix = $this->options['username'].':'.$this->options['password'].'@';
            }
            $hostname = $this->options['hostname'];
            $port = $this->options['port'];
            $database = $this->options['database'];

            $connectionStr = "mongodb://$prefix$hostname:$port/$database";

            $this->client = new MongoClient($connectionStr);
            $this->raw = $this->client->$database;
        }

        return $this->raw;
    }

    public function persist($collectionName, array $row)
    {
        $marshalledRow = $this->marshall($row);

        if (isset($row['$id'])) {
            $criteria = array(
                '_id' => new MongoId($row['$id']),
            );

            $marshalledRow = $this->getRaw()->$collectionName->findAndModify(
                $criteria,
                array('$set' => $marshalledRow),
                null,
                array('new' => true)
            );
        } else {
            $retval = $this->getRaw()->$collectionName->insert($marshalledRow);

            if (!$retval['ok']) {
                throw new Exception($retval['errmsg']);
            }
        }

        return $this->unmarshall($marshalledRow);
    }

    public function remove($collectionName, $rowId)
    {
        $result = $this->getRaw()->$collectionName->remove(['_id' => new MongoId($rowId)]);

        if ((int) $result['ok'] !== 1) {
            throw new Exception($result['errmsg']);
        }
    }

    public function cursorDistinct(Cursor $cursor)
    {
        throw new Exception('Unimplemented yet!');
    }

    public function cursorFetch(Cursor $cursor)
    {
        $rawCollection = $this->getRaw()->{$cursor->getCollectionId()};

        $criteria = $cursor->getCriteria();
        $rawCursor = empty($criteria) ?
            $rawCollection->find() :
            $rawCollection->find($this->translateCriteria($criteria));

        $sort = $cursor->getSort();
        if (isset($sort)) {
            $rawCursor->sort($sort);
        }

        $skip = $cursor->getSkip();
        if (isset($skip)) {
            $rawCursor->skip($skip);
        }

        $limit = $cursor->getLimit();
        if (isset($limit)) {
            $rawCursor->limit($limit);
        }

        return $rawCursor;
    }

    public function cursorSize(Cursor $cursor, $withLimitSkip = false)
    {
        throw new Exception('Unimplemented yet!');
    }

    public function cursorRead($context, $position = 0)
    {
        $ctxInfo = $context->info();
        if (!$ctxInfo['started_iterating']) {
            $context->next();
        } else {
            if ($position > $ctxInfo['at']) {
                $offset = $position - $ctxInfo['at'];
                for ($i = 0; $i < $offset; $i++) {
                    $context->next();
                }
            } elseif ($position < $ctxInfo['at']) {
                throw new Exception('Unimplemented backward');
            }
        }

        $found = $context->current();
        return is_null($found) ? null : $this->unmarshall($found);
    }

    public function unmarshall($object)
    {
        if (isset($object['_id'])) {
            $object['$id'] = (string) $object['_id'];
            unset($object['_id']);
        }

        foreach ($object as $key => &$value) {
            if ($value instanceof MongoDate) {
                $value = new DateTime('@'.$value->sec);
            } elseif ($value instanceof MongoId) {
                $value = (string) $value;
            }

            if ($key[0] === '_') {
                unset($object[$key]);
                $key[0] = '$';
                $object[$key] = $value;
            }
        }

        return $object;
    }

    /**
     * {@inheritDoc}
     */
    public function marshall($object)
    {
        if ($object instanceof NormDateTime) {
            return new MongoDate($object->getTimestamp());
        } elseif ($object instanceof UtilCollection) {
            return $object->toArray();
        } else {
            return parent::marshall($object);
        }
    }

    protected function translateCriteria($criteria)
    {
        return $criteria;
    }
}
