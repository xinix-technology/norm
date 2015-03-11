<?php namespace Norm\Connection;

use Norm\Connection;
use Norm\Collection;
use Norm\Cursor\FlatFileCursor;
use Rhumsaa\Uuid\Uuid;
use Exception;
use Norm\Model;

class FlatFileConnection extends Connection
{

    public $data = array();

    protected $dbPath = null;

    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if (!isset($this->options['dbPath'])) {
            $this->options['dbPath'] = 'data';
        }

        if (!isset($this->options['database'])) {
            throw new Exception("Please specify database name for your application", 1);
        }

        $dbPath = realpath('../'.$this->options['dbPath']);

        if (! $dbPath or ! realpath($dbPath.'/'.$this->options['database'])) {
            $this->prepareDatabaseEcosystem();
        }
    }

    protected function prepareDatabaseEcosystem()
    {
        $basePath = realpath('../');
        $dbPath = $basePath.'/'.$this->options['dbPath'].'/'.$this->options['database'];

        if (! is_dir($dbPath)) {
            mkdir($dbPath, 0755, true);
        }

        $this->dbPath = realpath($dbPath);
    }

    /**
     * @see Norm\Connection::query()
     */
    public function query($collection, array $criteria = null)
    {
        return new FlatFileCursor($this->factory($collection), $criteria);
    }

    /**
     * @see Norm\Connection::persist()
     */
    public function persist($collection, array $document)
    {
        if (is_null($this->dbPath)) {
            $this->prepareDatabaseEcosystem();
        }

        if ($collection instanceof Collection) {
            $collectionName = $collection->getName();
        } else {
            $collectionName = $collection;
            $collection     = static::factory($collection);
        }

        if (! isset($document['$id'])) {
            $document['$id'] = (string) Uuid::uuid1();
        }

        $marshalledDocument = $this->marshall($document);

        $marshalledDocument['$id'] = $document['$id'];

        $fileName = $this->dbPath.'/'.$collectionName.'/'.$marshalledDocument['$id'];

        file_put_contents($fileName, json_encode($marshalledDocument));

        return $this->unmarshall($marshalledDocument);
    }

    /**
     * @see Norm\Connection::remove()
     */
    public function remove($collection, $criteria = null)
    {
        if ($collection instanceof Collection) {
            $collection = $collection->getName();
        }

        if (is_null($this->dbPath)) {
            $this->prepareDatabaseEcosystem();
        }

        if (func_num_args() === 1) {
            $files = glob($this->dbPath.'/'.$collection.'/*'); // get all file names

            foreach($files as $file) { // iterate files
                if(is_file($file)) {
                    unlink($file); // delete file
                }
            }
        } elseif ($criteria instanceof Model) {
            $id = $criteria->getId();

            if (file_exists($this->dbPath.'/'.$collection.'/'.$id)) {
                unlink($this->dbPath.'/'.$collection.'/'.$id);
            }
        } else {
            $rows = $this->getCollectionData($collection, $criteria);

            foreach ($rows as $row) {
                $id = $row['$id'];

                if (file_exists($this->dbPath.'/'.$collection.'/'.$id)) {
                    unlink($this->dbPath.'/'.$collection.'/'.$id);
                }
            }
        }
    }

    /**
     * Getter for specific data for collection
     *
     * @return array
     */
    public function getCollectionData($collection, $criteria = null)
    {
        if (is_null($this->dbPath)) {
            $this->prepareDatabaseEcosystem();
        }

        if (! is_dir($this->dbPath.'/'.$collection)) {
            mkdir($this->dbPath.'/'.$collection, 0755, true);
        }

        $rows = array();

        if ($handle = opendir($this->dbPath.'/'.$collection)) {
            while (false !== ($entry = readdir($handle))) {
                if (! in_array($entry, array('.', '..'))) {
                    if (is_readable($pathToFile = $this->dbPath.'/'.$collection.'/'.$entry)) {
                        $match   = true;
                        $raw     = file_get_contents($pathToFile);
                        $content = json_decode($raw, true);

                        if (! is_null($criteria) and ! empty($criteria)) {
                            if (isset($criteria['!or'])) {
                                $string = reset(array_values(reset($criteria['!or'])));

                                if (! preg_match('/'.$string.'/', strtolower($raw))) {
                                    $match = false;
                                }
                            } else {
                                $intersection = array_intersect_assoc($content, $criteria);

                                if (empty($intersection)) {
                                    $match = false;
                                }
                            }

                        }

                        if ($match) {
                            $rows[] = $content;
                        }
                    }
                }
            }

            closedir($handle);
        }

        return $rows;
    }
}
