<?php
namespace Norm\Adapter;

use MongoId;
use MongoDate;
use MongoClient;
use Norm\Repository;
use Norm\Connection;
use Norm\Cursor;
use ROH\Util\Options;
use ROH\Util\Collection as UtilCollection;
use Norm\Exception\NormException;
// use Norm\Type\DateTime as NormDateTime;
use DateTime;

class Mongo extends Connection
{
    protected $context;

    protected $client;

    protected $connectionString;

    protected $database;

    public function __construct(Repository $repository, $id = 'main', array $options = [])
    {
        $prefix = '';
        if (isset($options['username'])) {
            $prefix = $options['username'].':'.$options['password'].'@';
        }
        $hostname = isset($options['hostname']) ? $options['hostname'] : MongoClient::DEFAULT_HOST;
        $port = isset($options['port']) ? $options['port'] : MongoClient::DEFAULT_PORT;

        if (!isset($options['database'])) {
            throw new NormException('Unspecified database name');
        }

        $this->connectionString = "mongodb://$prefix$hostname:$port";
        $this->database = $options['database'];

        parent::__construct($repository, $id);
    }

    public function getContext()
    {
        if (null === $this->context) {
            $this->client = new MongoClient($this->connectionString);
            $this->context = $this->client->{$this->database};
        }

        return $this->context;
    }

    public function persist($collectionId, array $row)
    {
        $marshalledRow = $this->marshall($row);

        if (isset($row['$id'])) {
            $criteria = [
                '_id' => new MongoId($row['$id']),
            ];

            $marshalledRow = $this->getContext()->$collectionId->findAndModify(
                $criteria,
                ['$set' => $marshalledRow],
                null,
                ['new' => true]
            );
        } else {
            $retval = $this->getContext()->$collectionId->insert($marshalledRow);

            // do we need this?
            // if (!$retval['ok']) {
            //     throw new NormException($retval['errmsg']);
            // }
        }

        return $this->unmarshall($marshalledRow);
    }

    public function remove(Cursor $cursor)
    {
        $result = $this->getContext()->{$cursor->getCollection()->getId()}
            ->remove($this->marshallCriteria($cursor->getCriteria()));

        // do we need this?
        // if ((int) $result['ok'] !== 1) {
        //     throw new NormException($result['errmsg']);
        // }
    }

    public function distinct(Cursor $cursor, $key)
    {
        $this->fetch($cursor);
        return $this->getContext()->{$cursor->getCollection()->getId()}->distinct($key);
    }

    protected function fetch(Cursor $cursor)
    {
        if (null === $cursor->getContext()) {
            $rawCollection = $this->getContext()->{$cursor->getCollection()->getId()};

            $criteria = $this->marshallCriteria($cursor->getCriteria());
            $rawCursor = empty($criteria) ?
                $rawCollection->find() :
                $rawCollection->find($criteria);

            $sort = $cursor->getSort();
            if (!empty($sort)) {
                $rawCursor->sort($sort);
            }

            $skip = $cursor->getSkip();
            if ($skip >= 0) {
                $rawCursor->skip($skip);
            }

            $limit = $cursor->getLimit();
            if ($limit  >= 0) {
                $rawCursor->limit($limit);
            }

            $cursor->setContext($rawCursor);
        }

        return $cursor->getContext();
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        $this->fetch($cursor);
        $context = $cursor->getContext();
        return $context->count($withLimitSkip);
    }

    public function read(Cursor $cursor)
    {
        $this->fetch($cursor);
        $position = $cursor->key();
        $context = $cursor->getContext();
        $ctxInfo = $context->info();

        if (!$ctxInfo['started_iterating']) {
            $context->next();
        } else {
            if ($position > $ctxInfo['at']) {
                $offset = $position - $ctxInfo['at'];
                for ($i = 0; $i < $offset; $i++) {
                    $context->next();
                }
            // } elseif ($position < $ctxInfo['at']) {
            //     throw new NormException('Unimplemented backward');
            }
        }

        $found = $context->current();
        return null === $found ? null : $this->unmarshall($found);
    }

    public function unmarshallKV($key, $value)
    {
        if ('_id' === $key) {
            return ['$id', (string) $value];
        }

        if ($value instanceof MongoDate) {
            $value = new DateTime('@'.$value->sec);
        } elseif ($value instanceof MongoId) {
            $value = (string) $value;
        }

        return parent::unmarshallKV($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function marshallKV($key, $value)
    {
        if ($value instanceof DateTime) {
            $value = new MongoDate($value->getTimestamp());
        } elseif ($value instanceof UtilCollection) {
            $value = $value->toArray();
        }

        return parent::marshallKV($key, $value);
    }

    public function marshallCriteria(array $criteria)
    {
        return $criteria;
    }
}
