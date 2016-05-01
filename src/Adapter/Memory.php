<?php
namespace Norm\Adapter;

use Norm\Cursor;
use Norm\Connection;
use Norm\Collection;
use Norm\Exception\NormException;
use Rhumsaa\Uuid\Uuid;

class Memory extends Connection
{
    /**
     * [$context description]
     * @var array
     */
    protected $context = [];

    public function getContext()
    {
        return $this->context;
    }

    public function persist($collectionId, array $row)
    {
        $this->context[$collectionId] = isset($this->context[$collectionId]) ?
            $this->context[$collectionId] : [];

        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $this->context[$collectionId][$id] = $row;

        return $this->unmarshall($row);
    }

    public function remove(Cursor $cursor)
    {
        $collectionId = $cursor->getCollection()->getId();
        if (empty($cursor->getCriteria()) && $cursor->getSkip() === 0 && $cursor->getLimit() === -1) {
            // all
            $this->context[$collectionId] = [];
        } else {
            // partial
            $this->context[$collectionId] = isset($this->context[$collectionId]) ?
                $this->context[$collectionId] : [];

            foreach ($cursor as $row) {
                if (isset($this->context[$collectionId][$row['$id']])) {
                    unset($this->context[$collectionId][$row['$id']]);
                }
            }
        }
    }

    public function distinct(Cursor $cursor, $key)
    {
        $context = $this->fetch($cursor);

        $result = [];
        foreach ($context as $row) {
            $v = $row[$key];
            if (!in_array($v, $result)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    protected function fetch(Cursor $cursor)
    {
        if (null === ($cursorContext = $cursor->getContext())) {
            $query = [
                'criteria' => $this->marshallCriteria($cursor->getCriteria()),
                'limit' => $cursor->getLimit(),
                'skip' => $cursor->getSkip(),
                'sort' => $this->marshall($cursor->getSort()),
            ];

            $collectionId = $cursor->getCollection()->getId();
            $allContext = isset($this->context[$collectionId]) ? $this->context[$collectionId] : [];
            $cursorContext = [];

            $i = 0;
            $skip = 0;
            foreach ($allContext as $key => $value) {
                if ($this->criteriaMatch($value, $query['criteria'])) {
                    if (isset($query['skip']) && $query['skip'] > $skip) {
                        $skip++;
                        continue;
                    }

                    $cursorContext[] = $value;

                    $i++;
                    if (isset($query['limit']) && $query['limit'] == $i) {
                        break;
                    }
                }
            }

            $sortValues = $query['sort'];
            if (!empty($sortValues)) {
                usort($cursorContext, function ($a, $b) use ($sortValues) {
                    $value = 0;
                    foreach ($sortValues as $sortKey => $sortVal) {
                        $aKey = isset($a[$sortKey]) ? $a[$sortKey] : null;
                        $bKey = isset($b[$sortKey]) ? $b[$sortKey] : null;
                        $value = strcmp($aKey, $bKey) * $sortVal * -1;
                        if ($value !== 0) {
                            break;
                        }
                    }
                    return $value;
                });
            }

            $cursor->setContext($cursorContext);
        }

        return $cursorContext;
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

    public function read(Cursor $cursor)
    {
        $cursorContext = $this->fetch($cursor);
        return isset($cursorContext[$cursor->key()]) ? $this->unmarshall($cursorContext[$cursor->key()]) : null;
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
