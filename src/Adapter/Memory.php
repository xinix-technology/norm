<?php
namespace Norm\Adapter;

use Norm\Query;
use Norm\Cursor;
use Norm\Connection;
use ROH\Util\Collection;
use Norm\Exception\NormException;
use Rhumsaa\Uuid\Uuid;

class Memory extends Connection
{
    public function __construct(Collection $data = null)
    {
        $this->data = $data ?: new Collection();
    }

    protected function execBegin()
    {
        // do nothing
    }

    protected function execCommit()
    {
        // do nothing
    }

    protected function execRollback()
    {
        // do nothing
    }

    public function insert(Query $query, callable $callback)
    {
        $name = $query->getSchema()->getName();
        $data = $this->data[$name] ?: [];

        $count = 0;
        foreach ($query->getRows() as $row) {
            $row = $row->toArray();
            $row['id'] = Uuid::uuid4()->__toString();

            $data[] = $row;
            $callback($row);
            $count++;
        }

        $this->data[$name] = $data;

        return $count;
    }

    public function count(Query $query, bool $useSkipAndLimit = false)
    {
        $skip = $query->getSkip();
        $limit = $query->getLimit();

        if (!$useSkipAndLimit) {
            $query->skip(0)->limit(-1);
        }

        $count = 0;
        $this->load($query, function () use (&$count) {
            $count++;
        });

        $query->skip($skip)->limit($limit);

        return $count;
    }

    public function load(Query $query, callable $callback)
    {
        $data = $this->data[$query->getSchema()->getName()] ?: [];

        $criteria = $query->getCriteria();
        $sorts = $query->getSort();
        $skip = $query->getSkip();
        $limit = $query->getLimit();

        if (isset($criteria) && isset($criteria['id'])) {
            foreach ($data as $row) {
                if ($row['id'] === $criteria['id']) {
                    $foundRow = $row;
                    break;
                }
            }
            $data = isset($foundRow) ? [ $foundRow ] : [];
        } else {
            $rows = [];
            foreach ($data as $row) {
                if ($this->matchCriteria($criteria, $row)) {
                    $rows[] = $row;
                }
            }

            // FIXME: implement sort

            if ($skip < 0) {
                $skip = 0;
            }

            if ($limit < 0) {
                $data = array_slice($rows, $skip);
            } else {
                $data = array_slice($rows, $skip, $skip + $limit);
            }
        }

        $count = 0;
        foreach ($data as $row) {
            $callback($row);
            $count++;
        }

        return $count;
    }

    protected function matchCriteria($criteria, $value)
    {
        foreach ($criteria as $ck => $cv) {
            if ($ck === '!or') {
                $valid = false;
                foreach ($cv as $subCriteria) {
                    if ($this->matchCriteria($value, $subCriteria)) {
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

    public function update(Query $query)
    {
        $name = $query->getSchema()->getName();
        $data = $this->data[$name] ?: [];

        $count = $this->load($query, function ($found) use (&$data, $query) {
            foreach ($data as $key => &$row) {
                if ($row['id'] === $found['id']) {
                    foreach ($query->getSets() as $key => $value) {
                        $row[$key] = $value;
                    }
                }
            }
        });

        $this->data[$name] = $data;

        return $count;
    }

    public function delete(Query $query)
    {
        $name = $query->getSchema()->getName();
        $data = $this->data[$name] ?: [];

        $count = $this->load($query, function ($found) use (&$data, $query) {
            foreach ($data as $key => $row) {
                if ($row['id'] === $found['id']) {
                    array_splice($data, $key, 1);
                    return;
                }
            }
        });

        $this->data[$name] = $data;

        return $count;
    }

    // /**
    //  * [$context description]
    //  * @var array
    //  */
    // protected $context = [];

    // public function getContext()
    // {
    //     return $this->context;
    // }

    // public function persist($collectionId, array $row)
    // {
    //     $this->context[$collectionId] = isset($this->context[$collectionId]) ?
    //         $this->context[$collectionId] : [];

    //     $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

    //     $row = $this->marshall($row);
    //     $row['id'] = $id;

    //     $this->context[$collectionId][$id] = $row;

    //     return $this->unmarshall($row);
    // }

    // public function remove(Cursor $cursor)
    // {
    //     $collectionId = $cursor->getCollection()->getId();
    //     if (empty($cursor->getCriteria()) && $cursor->getSkip() === 0 && $cursor->getLimit() === -1) {
    //         // all
    //         $this->context[$collectionId] = [];
    //     } else {
    //         // partial
    //         $this->context[$collectionId] = isset($this->context[$collectionId]) ?
    //             $this->context[$collectionId] : [];

    //         foreach ($cursor as $row) {
    //             if (isset($this->context[$collectionId][$row['$id']])) {
    //                 unset($this->context[$collectionId][$row['$id']]);
    //             }
    //         }
    //     }
    // }

    // public function distinct(Cursor $cursor, $key)
    // {
    //     $context = $this->fetch($cursor);

    //     $result = [];
    //     foreach ($context as $row) {
    //         $v = $row[$key];
    //         if (!in_array($v, $result)) {
    //             $result[] = $v;
    //         }
    //     }
    //     return $result;
    // }

    // protected function fetch(Cursor $cursor)
    // {
    //     if (null === ($cursorContext = $cursor->getContext())) {
    //         $query = [
    //             'criteria' => $this->marshallCriteria($cursor->getCriteria()),
    //             'limit' => $cursor->getLimit(),
    //             'skip' => $cursor->getSkip(),
    //             'sort' => $this->marshall($cursor->getSort()),
    //         ];

    //         $collectionId = $cursor->getCollection()->getId();
    //         $allContext = isset($this->context[$collectionId]) ? $this->context[$collectionId] : [];
    //         $cursorContext = [];

    //         $i = 0;
    //         $skip = 0;
    //         foreach ($allContext as $key => $value) {
    //             if ($this->criteriaMatch($value, $query['criteria'])) {
    //                 if (isset($query['skip']) && $query['skip'] > $skip) {
    //                     $skip++;
    //                     continue;
    //                 }

    //                 $cursorContext[] = $value;

    //                 $i++;
    //                 if (isset($query['limit']) && $query['limit'] == $i) {
    //                     break;
    //                 }
    //             }
    //         }

    //         $sortValues = $query['sort'];
    //         if (!empty($sortValues)) {
    //             usort($cursorContext, function ($a, $b) use ($sortValues) {
    //                 $value = 0;
    //                 foreach ($sortValues as $sortKey => $sortVal) {
    //                     $aKey = isset($a[$sortKey]) ? $a[$sortKey] : null;
    //                     $bKey = isset($b[$sortKey]) ? $b[$sortKey] : null;
    //                     $value = strcmp($aKey, $bKey) * $sortVal * -1;
    //                     if ($value !== 0) {
    //                         break;
    //                     }
    //                 }
    //                 return $value;
    //             });
    //         }

    //         $cursor->setContext($cursorContext);
    //     }

    //     return $cursorContext;
    // }

    // public function size(Cursor $cursor, $withLimitSkip = false)
    // {
    //     $clone = clone $cursor;
    //     if ($withLimitSkip) {
    //         return count($clone->toArray());
    //     } else {
    //         return count($clone->limit(-1)->skip(0)->toArray());
    //     }
    // }

    // public function read(Cursor $cursor)
    // {
    //     $cursorContext = $this->fetch($cursor);
    //     $key = $cursor->key();
    //     return isset($cursorContext[$key]) ? $this->unmarshall($cursorContext[$key]) : null;
    // }
}
