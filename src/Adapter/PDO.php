<?php
namespace Norm\Adapter;

use Norm\Exception\NormException;
use PDO as ThePDO;
use Norm\Cursor;
use Norm\Repository;
use Norm\Connection;
use Norm\Dialect\MySql;
use Norm\Dialect\Sqlite;
use ROH\Util\Collection as UtilCollection;

class PDO extends Connection
{
    const DIALECTS = [
        'mysql' => Mysql::class,
        'sqlite' => Sqlite::class,
    ];

    protected $context;

    protected $prefix;

    protected $dsn;

    protected $dialect;

    protected $user;

    protected $password;

    protected $pdoOptions;

    public function __construct(Repository $repository, $id = 'main', array $options = [])
    {
        parent::__construct($repository, $id);

        if (!isset($options['dsn'])) {
            throw new NormException('DSN is required');
        }

        $this->dsn = $options['dsn'];
        $this->prefix = parse_url($this->dsn, PHP_URL_SCHEME);

        $Dialect = static::DIALECTS[$this->prefix];
        $this->dialect = new $Dialect($this);

        $this->pdoOptions = [
            ThePDO::ATTR_ERRMODE => ThePDO::ERRMODE_EXCEPTION,
            ThePDO::ATTR_EMULATE_PREPARES => false,
            ThePDO::ATTR_DEFAULT_FETCH_MODE => ThePDO::FETCH_ASSOC,
        ];
    }

    public function getContext()
    {
        if (null === $this->context) {
            $this->context = new ThePDO($this->dsn, $this->user, $this->password, $this->pdoOptions);
        }

        return $this->context;
    }

    public function persist($collectionId, array $row)
    {
        $marshalledRow = $this->marshall($row);

        if (isset($row['$id'])) {
            $sql = $this->dialect->grammarUpdate($collectionId, $marshalledRow, ['id' => $row['$id']]);
            $marshalledRow['id'] = $row['$id'];

            $this->execute($sql, $marshalledRow);
        } else {
            $sql = $this->dialect->grammarInsert($collectionId, $marshalledRow);

            $this->execute($sql, $marshalledRow);

            $id = $this->getContext()->lastInsertId();
            if (null !== $id) {
                $marshalledRow['id'] = $id;
            }
        }

        return $this->unmarshall($marshalledRow);
    }

    public function remove(Cursor $cursor)
    {
        $sql = $this->dialect->grammarDelete($cursor->getCollection()->getId(), $cursor->getCriteria());
        return $this->execute($sql, $cursor->getCriteria());
    }

    public function distinct(Cursor $cursor, $key)
    {
        $sql = $this->dialect->grammarDistinct($cursor->getCollection()->getId(), 'foo');
        $statement = $this->query($sql, $cursor->getCriteria());
        $result = [];
        foreach ($statement as $row) {
            $result[] = $row[$key];
        }
        return $result;
    }

    protected function fetch(Cursor $cursor)
    {
        if (null === $cursor->getContext()) {
            $sql = $this->dialect->grammarSelect($cursor->getCollection()->getId());
            $statement = $this->getContext()->prepare($sql);
            $statement->execute();

            $cursor->setContext(new UtilCollection([
                'statement' => $statement,
                'current' => 0,
                'cache' => new UtilCollection(),
            ]));
        }
    }

    public function size(Cursor $cursor, $withLimitSkip = false)
    {
        $sql = $this->dialect->grammarCount(
            $cursor->getCollection()->getId(),
            $cursor->getCriteria(),
            $cursor->getSort(),
            $cursor->getSkip(),
            $cursor->getLimit()
        );
        $result = $this->query($sql, $cursor->getCriteria());
        return (int) $result->fetch()['count'];
    }

    public function read(Cursor $cursor)
    {
        $this->fetch($cursor);

        $expectedPosition = $cursor->key();
        $cursorContext = $cursor->getContext();
        $currentPosition = $cursorContext['current'];
        $cache = $cursorContext['cache'];
        $statement = $cursorContext['statement'];

        if ($expectedPosition >= $currentPosition) {
            $row = null;
            for ($i = $currentPosition; $i <= $expectedPosition; $i++) {
                if (!isset($cache[$i]) && ($fetched = $statement->fetch())) {
                    $cache[$i] = $this->unmarshall($fetched);
                    $cursorContext['current'] = $i;
                }
            }
        }

        return isset($cache[$expectedPosition]) ? $cache[$expectedPosition] : null;
    }

    protected function query($sql, array $data = [])
    {
        $statement = $this->getContext()->prepare($sql);

        $statement->execute($data);

        return $statement;
    }

    protected function execute($sql, array $data = [])
    {
        $statement = $this->getContext()->prepare($sql);

        return $statement->execute($data);
    }
}
