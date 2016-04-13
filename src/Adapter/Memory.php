<?php
namespace Norm\Adapter;

use Norm\Cursor;
use Norm\Connection;
use Norm\Exception\NormException;
use Rhumsaa\Uuid\Uuid;

class Memory extends Connection
{
    protected $context;

    public function __construct($id, array $options = [])
    {
        parent::__construct($id);
    }

    public function getContext()
    {
        return $this->context;
    }

    public function persist($collectionName, array $row)
    {
        $this->context[$collectionName] = isset($this->context[$collectionName]) ?
            $this->context[$collectionName] : [];

        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $this->context[$collectionName][$id] = $row;

        return $this->unmarshall($row);
    }

    public function remove($collectionName, $rowId)
    {
        $this->context[$collectionName] = isset($this->context[$collectionName]) ?
            $this->context[$collectionName] : [];

        if (isset($this->context[$collectionName][$rowId])) {
            unset($this->context[$collectionName][$rowId]);
        }
    }

    public function cursorDistinct(Cursor $cursor)
    {
        throw new \Exception('Unimplemented yet!');
    }

    public function cursorFetch(Cursor $cursor)
    {
        $criteria = $this->marshall($cursor->getCriteria(), 'id');

        $query = [
            'criteria' => $criteria,
            'limit' => $cursor->getLimit(),
            'skip' => $cursor->getSkip(),
            'sort' => $cursor->getSort(),
        ];

        $collectionId = $cursor->getCollectionId();
        $contextAll = isset($this->context[$collectionId]) ? $this->context[$collectionId] : [];
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

    public function cursorSize(Cursor $cursor, $withLimitSkip = false)
    {
        $clone = clone $cursor;
        if ($withLimitSkip) {
            return count($clone->toArray());
        } else {
            $clone->limit(-1)->skip(0);
        }
    }

    public function cursorRead($context, $position = 0)
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
                        if ($cv >= $rowValue) {
                            return false;
                        }
                        break;
                    case 'lte':
                        if ($cv > $rowValue) {
                            return false;
                        }
                        break;
                    case 'gt':
                        if ($cv <= $rowValue) {
                            return false;
                        }
                        break;
                    case 'gte':
                        if ($cv < $rowValue) {
                            return false;
                        }
                        break;
                    case 'in':
                        if (!in_array($rowValue, $cv)) {
                            return false;
                        }
                        break;
                    default:
                        throw new NormException("Operator '$operator' is not implemented yet!");
                }
            }
        }
        return true;
    }
}
