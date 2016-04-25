<?php
namespace Norm\Adapter;

use Norm\Cursor;
use Norm\Connection;
use Norm\Exception\NormException;
use Rhumsaa\Uuid\Uuid;

class Memory extends Connection
{
    /**
     * [$raw description]
     * @var array
     */
    protected $raw = [];

    public function getRaw()
    {
        return $this->raw;
    }

    public function persist($collectionName, array $row)
    {
        $this->raw[$collectionName] = isset($this->raw[$collectionName]) ?
            $this->raw[$collectionName] : [];

        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $this->raw[$collectionName][$id] = $row;

        return $this->unmarshall($row);
    }

    public function remove(Cursor $cursor)
    {
        $collectionId = $cursor->getCollection()->getId();
        if (empty($cursor->getCriteria()) && $cursor->getSkip() === 0 && $cursor->getLimit() === -1) {
            // all
            $this->raw[$collectionId] = [];
        } else {
            // partial
            $this->raw[$collectionId] = isset($this->raw[$collectionId]) ?
                $this->raw[$collectionId] : [];

            foreach ($cursor as $row) {
                if (isset($this->raw[$collectionId][$row['$id']])) {
                    unset($this->raw[$collectionId][$row['$id']]);
                }
            }
        }
    }

    public function distinct(Cursor $cursor)
    {
        throw new NormException('Unimplemented yet!');
    }

    public function fetch(Cursor $cursor)
    {
        $criteria = $this->marshall($cursor->getCriteria(), 'id');

        $query = [
            'criteria' => $criteria,
            'limit' => $cursor->getLimit(),
            'skip' => $cursor->getSkip(),
            'sort' => $cursor->getSort(),
        ];

        $collectionId = $cursor->getCollection()->getId();
        $contextAll = isset($this->raw[$collectionId]) ? $this->raw[$collectionId] : [];
        $context = [];

        $i = 0;
        $skip = 0;
        foreach ($contextAll as $key => $value) {
            if ($this->criteriaMatch($value, $query['criteria'])) {
                if (isset($query['skip']) && $query['skip'] > $skip) {
                    $skip++;
                    continue;
                }

                $context[] = $value;

                $i++;
                if (isset($query['limit']) && $query['limit'] == $i) {
                    break;
                }
            }
        }

        $sortValues = $query['sort'];
        if (empty($sortValues)) {
            return $context;
        }

        usort($context, function ($a, $b) use ($sortValues) {
            $context = 0;
            foreach ($sortValues as $sortKey => $sortVal) {
                $aKey = isset($a[$sortKey]) ? $a[$sortKey] : null;
                $bKey = isset($b[$sortKey]) ? $b[$sortKey] : null;
                $context = strcmp($aKey, $bKey) * $sortVal * -1;
                if ($context !== 0) {
                    break;
                }
            }
            return $context;
        });

        return $context;
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        $clone = clone $cursor;
        if ($withLimitSkip) {
            return count($clone->toArray());
        } else {
            return count($clone->limit(-1)->skip(0)->toArray());
        }
    }

    public function read($context, $position = 0)
    {
        return isset($context[$position]) ? $this->unmarshall($context[$position]) : null;
    }

    protected function criteriaMatch($value, $criteria)
    {
        foreach ($criteria as $ck => $cv) {
            if ($ck === '!or') {
                $valid = false;
                foreach ($cv as $subCriteria) {
                    if ($this->criteriaMatch($value, $subCriteria)) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    return false;
                }
            } else {

                $query = explode('!', $ck);
                $op = isset($query[1]) ? $query[1] : 'eq';
                $key = $query[0];

                $rowValue = isset($value[$key]) ? $value[$key] : null;
                switch ($op) {
                    case 'eq':
                        if ($cv !== $rowValue) {
                            return false;
                        }
                        break;
                    case 'ne':
                        if ($cv == $rowValue) {
                            return false;
                        }
                        break;
                    case 'lt':
                        if ($cv <= $rowValue) {
                            return false;
                        }
                        break;
                    case 'lte':
                        if ($cv < $rowValue) {
                            return false;
                        }
                        break;
                    case 'gt':
                        if ($cv >= $rowValue) {
                            return false;
                        }
                        break;
                    case 'gte':
                        if ($cv > $rowValue) {
                            return false;
                        }
                        break;
                    case 'in':
                        if (!in_array($rowValue, $cv)) {
                            return false;
                        }
                        break;
                    default:
                        throw new NormException("Operator '$op' is not implemented yet!");
                }
            }
        }
        return true;
    }
}
