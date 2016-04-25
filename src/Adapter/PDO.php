<?php
namespace Norm\Adapter;

use Exception;
use InvalidArgumentException;
use PDO as ThePDO;
use Norm\Cursor;
use Norm\Connection;
use Norm\Dialect\MySql;
use Norm\Dialect\Sqlite;
use ROH\Util\Collection;

class PDO extends Connection
{
    protected $DIALECT_MAP = [
        'mysql' => MySql::class,
        'sqlite' => Sqlite::class,
    ];

    protected $prefix;

    protected $dsn;

    protected $dialect;

    protected $user;

    protected $password;

    protected $pdoOptions;

    public function __construct($id, array $options = [])
    {
        parent::__construct($id);

        if (!isset($options['dsn'])) {
            throw new InvalidArgumentException('DSN is required');
        }

        $this->dsn = $options['dsn'];
        $this->prefix = parse_url($this->dsn, PHP_URL_SCHEME);

        $Dialect = $this->DIALECT_MAP[$this->prefix];
        $this->dialect = new $Dialect($this);

        $this->pdoOptions = [
            ThePDO::ATTR_ERRMODE => ThePDO::ERRMODE_EXCEPTION,
            ThePDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    public function getRaw()
    {
        if (is_null($this->raw)) {
            $this->raw = new ThePDO($this->dsn, $this->user, $this->password, $this->pdoOptions);
        }

        return $this->raw;
    }

    public function persist($collectionName, array $row)
    {
        $marshalledRow = $this->marshall($row);

        if (isset($row['$id'])) {
            $sql = $this->dialect->grammarUpdate($collectionName, $marshalledRow, ['id' => $row['$id']]);
            $marshalledRow['id'] = $row['$id'];

            $this->execute($sql, $marshalledRow);
        } else {
            $sql = $this->dialect->grammarInsert($collectionName, $marshalledRow);

            $succeed = $this->execute($sql, $marshalledRow);

            if ($succeed) {
                $id = $this->getRaw()->lastInsertId();
            } else {
                throw new Exception('PDO Insert error.');
            }

            if (!is_null($id)) {
                $marshalledRow['id'] = $id;
            }
        }

        return $this->unmarshall($marshalledRow);
    }

    public function remove($collectionName, $rowId)
    {
        $sql = $this->dialect->grammarDelete($collectionName, $rowId);
        return $this->execute($sql, ['id' => $rowId]);
    }

    public function distinct(Cursor $cursor)
    {
        throw new Exception('Unimplemented yet!');
    }

    public function fetch(Cursor $cursor)
    {
        $sql = $this->dialect->grammarSelect($cursor->getCollection()->getId());
        $statement = $this->getRaw()->prepare($sql);
        $statement->execute();
        return new Collection([
            'statement' => $statement,
            'position' => 0,
            'cache' => new Collection(),
        ]);
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        throw new Exception('Unimplemented yet!');
    }

    public function read($context, $position = 0)
    {
        if ($position >= $context['position']) {
            for ($i = $context['position']; $i <= $position; $i++) {
                if (!isset($context['cache'][$i])) {
                    $row = $context['statement']->fetch(ThePDO::FETCH_ASSOC);
                    if ($row) {
                        $context['cache'][$i] = $row;
                        $context['position'] = $i;
                    }
                } else {
                    $row = $context['cache'][$i];
                }

                if ($i === $position) {
                    return $row ? $this->unmarshall($row) : null;
                }
            }
        } else {
            throw new Exception('Unimplemented yet!');
        }
    }

    protected function execute($sql, array $data = [])
    {
        $statement = $this->getRaw()->prepare($sql);

        return $statement->execute($data);
    }
}
