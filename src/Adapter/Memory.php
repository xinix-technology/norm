<?php
namespace Norm\Adapter;

use Norm\Connection;
use Norm\Collection;
use Norm\Cursor;

use Rhumsaa\Uuid\Uuid;

class Memory extends Connection
{
    protected $context;

    public function getContext()
    {
        return $this->context;
    }

    public function persist($collectionName, array $row)
    {
        $this->context[$collectionName] = isset($this->context[$collectionName]) ?
            $this->context[$collectionName] : array();

        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $this->context[$collectionName][$id] = $row;

        return $this->unmarshall($row);
    }

    public function remove($collectionName, $rowId)
    {
        $this->context[$collectionName] = isset($this->context[$collectionName]) ?
            $this->context[$collectionName] : array();

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
        $criteria = $cursor->getCriteria();

        $collectionId = $cursor->getCollectionId();
        $contextAll = isset($this->context[$collectionId]) ? $this->context[$collectionId] : [];
        $context = [];
        foreach ($contextAll as $key => $value) {
            if ($this->criteriaMatch($value, $criteria)) {
                $context[] = $value;
            }
        }

        return $context;
    }

    public function cursorSize(Cursor $cursor, $withLimitSkip = false)
    {
        throw new \Exception('Unimplemented yet!');
    }

    public function cursorRead($context, $position = 0)
    {
        return isset($context[$position]) ? $this->unmarshall($context[$position]) : null;
    }

    protected function criteriaMatch($value, $criteria)
    {
        foreach ($criteria as $ck => $cv) {
            $query = explode('!', $ck);
            $op = isset($query[1]) ? $query[1] : 'eq';
            $key = $query[0];

            switch ($op) {
                case 'eq':
                    if ($value[$key] !== $cv) {
                        return false;
                    }
                    break;
                default:
                    throw new \Exception('Unimplemented');
            }
        }
        return true;
    }
}
