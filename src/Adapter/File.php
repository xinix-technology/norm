<?php
namespace Norm\Adapter;

use Norm\Cursor;
use Norm\Collection;
use Norm\Exception\NormException;
use Rhumsaa\Uuid\Uuid;

class File extends Memory
{
    protected $dataDir;

    public function __construct(Repository $repository = null, $id = 'main', array $options = [])
    {
        parent::__construct($repository, $id);

        if (!isset($options['dataDir'])) {
            throw new NormException(
                'File adapter does not have data directory is not available, please check your configuration.'
            );
        }

        $this->dataDir = $options['dataDir'];
    }

    public function persist($collectionId, array $row)
    {
        $id = isset($row['$id']) ? $row['$id'] : Uuid::uuid1()->__toString();

        $row = $this->marshall($row);
        $row['id'] = $id;

        $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $collectionId . DIRECTORY_SEPARATOR;

        if (!is_dir($collectionDir)) {
            mkdir($collectionDir, 0755, true);
        }

        file_put_contents($collectionDir . $row['id'] . '.json', json_encode($row, JSON_PRETTY_PRINT));

        return $this->unmarshall($row);
    }

    public function remove(Cursor $cursor)
    {
        $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $cursor->getCollection()->getId() . DIRECTORY_SEPARATOR;

        foreach ($cursor as $row) {
            @unlink($collectionDir . $row['$id'] . '.json');
        }
    }

    public function distinct(Cursor $cursor, $key)
    {
        $this->fetch($cursor);

        $result = [];
        foreach ($cursor->getContext() as $row) {
            $v = $row[$key];
            if (!in_array($v, $result)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    protected function fetch(Cursor $cursor)
    {
        if (null === $cursor->getContext()) {
            $cursorContext = [];

            $query = [
                'criteria' => $this->marshallCriteria($cursor->getCriteria()),
                'limit' => $cursor->getLimit(),
                'skip' => $cursor->getSkip(),
                'sort' => $cursor->getSort(),
            ];

            $collectionId = $cursor->getCollection()->getId();

            $collectionDir = $this->dataDir . DIRECTORY_SEPARATOR . $collectionId . DIRECTORY_SEPARATOR;

            if (!is_dir($collectionDir)) {
                @mkdir($collectionDir, 0755, true);
            } elseif ($dh = opendir($collectionDir)) {
                $i = 0;
                $skip = 0;

                while (($file = readdir($dh)) !== false) {
                    $filename = $collectionDir . $file;
                    if (is_file($filename)) {
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        if (strtolower($ext) === 'json') {
                            $row = file_get_contents($filename);
                            $row = json_decode($row, true);

                            if ($this->criteriaMatch($row, $query['criteria'])) {
                                if (isset($query['skip']) && $query['skip'] > $skip) {
                                    $skip++;
                                    continue;
                                }

                                $cursorContext[] = $row;

                                $i++;
                                if (isset($query['limit']) && $query['limit'] == $i) {
                                    break;
                                }
                            }
                        }
                    }
                }
                closedir($dh);
            }

            $sortValues = $query['sort'];
            if (!empty($sortValues)) {
                usort($cursorContext, function ($a, $b) use ($sortValues) {
                    $value = 0;
                    foreach ($sortValues as $sortKey => $sortVal) {
                        $value = strcmp($a[$sortKey], $b[$sortKey]) * $sortVal * -1;
                        if ($value !== 0) {
                            break;
                        }
                    }
                    return $value;
                });
            }

            $cursor->setContext($cursorContext);
            return $cursorContext;
        }
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        $this->fetch($cursor);
        return count($cursor->getContext());
    }

    // public function marshallCriteria(array $criteria, $deep = false)
    // {
    //     var_dump(parent::marshallCriteria($criteria, $deep));
    //     exit;
    //     // $newCriteria = [];
    //     // foreach ($criteria as $key => $value) {
    //     //     var_dump($key);
    //     //     exit;
    //     //     // $newCriteria
    //     // }
    //     // return $newCriteria;
    // }
}
