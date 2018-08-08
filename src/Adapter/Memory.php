<?php
namespace Norm\Adapter;

use Norm\Query;
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
}
