<?php namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Rhumsaa\Uuid\Uuid;
use Norm\Cursor\MemoryCursor;

/**
 * Memory Connection.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class MemoryConnection extends Connection
{
    public $data = array();

    /**
     * @see Norm\Connection::query()
     */
    public function query($collection, array $criteria = null)
    {
        return new MemoryCursor($this->factory($collection), $criteria);
    }

    /**
     * @see Norm\Connection::persist()
     */
    public function persist($collection, array $document)
    {
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        }

        $this->data[$collection] = isset($this->data[$collection]) ? $this->data[$collection] : array();
        // TODO change this to uuidv4
        $document['id'] = Uuid::uuid1().'';

        $document = $this->marshall($document);

        $this->data[$collection][] = $document;

        return $this->unmarshall($document);
    }

    /**
     * @see Norm\Connection::remove()
     */
    public function remove($collection, $criteria = null)
    {
        if ($collection instanceof \Norm\Collection) {
            $collection = $collection->getName();
        }

        if (func_num_args() === 1) {
            $this->data[$collection] = array();
        } elseif ($criteria instanceof \Norm\Model) {
            $id = $criteria->getId();
            $index = -1;
            foreach ($this->data[$collection] as $k => $row) {
                if ($row['id'] === $id) {
                    $index = $k;
                    break;
                }
            }

            array_splice($this->data[$collection], $k, 1);
        } else {
            throw new \Exception('Unimplemented yet!');
        }
    }

    public function setCollectionData($collection, array $rows)
    {
        $this->data[$collection] = array();

        foreach ($rows as $i => &$row) {
            $this->persist($collection, $row);
        }
    }

    /**
     * Getter for specific data for collection
     * @return array
     */
    public function getCollectionData($collection)
    {
        if (isset($this->data[$collection])) {
            return $this->data[$collection];
        } else {
            return array();
        }
    }
}
