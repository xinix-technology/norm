<?php

namespace Norm;

use ROH\Util\Options;
use ROH\Util\Collection;

class Query
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var int
     */
    protected $affected = 0;

    protected $rows = [];

    /**
     * @var Model
     */
    protected $sets;

    protected $ilimit = -1;

    protected $iskip = 0;

    protected $sorts;

    /**
     * @var string
     */
    protected $connection;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var string
     */
    protected $mode = '';

    /**
     * @var array
     */
    protected $criteria;

    public function __construct(Session $session, $schema, $criteria = [])
    {
        $this->session = $session;
        [ $this->connection, $this->schema ] = $this->session->parseSchema($schema);

        $this->find($criteria);
    }

    /**
     * Getter skip
     *
     * @return int
     */
    public function getSkip()
    {
        return $this->iskip;
    }

    /**
     * Getter limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->ilimit;
    }

    /**
     * Getter schema
     *
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Getter criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Getter sorts
     *
     * @return array
     */
    public function getSort()
    {
        return $this->sorts;
    }

    /**
     * Set criteria
     *
     * @param array $criteria
     * @return Query
     */
    public function find($criteria = [])
    {
        $this->criteria = is_array($criteria) ? $criteria : [ 'id' => $criteria ];
        return $this;
    }

    /**
     * Insert new row
     *
     * @param array $row
     * @return Query
     */
    public function insert(array $row)
    {
        $this->mode = 'insert';

        $this->rows[] = $this->schema->attach($row);
        return $this;
    }

    /**
     * Set sort
     *
     * @param array $sorts
     * @return Query
     */
    public function sort(array $sorts)
    {
        $this->sorts = $sorts;
        return $this;
    }

    /**
     * Set limit
     *
     * @param int $limit
     * @return Query
     */
    public function limit($limit)
    {
        $this->ilimit = $limit;
        return $this;
    }

    /**
     * Set skip
     *
     * @param int $skip
     * @return Query
     */
    public function skip($skip)
    {
        $this->iskip = $skip;
        return $this;
    }

    /**
     * Set updated fields
     *
     * @param array $sets
     * @return Query
     */
    public function set(array $sets)
    {
        $this->mode = 'update';

        $this->sets = $this->schema->attach($sets);
        return $this;
    }

    /**
     * Count rows
     *
     * @param bool    $useSkipAndLimit
     * @return int
     */
    public function count($useSkipAndLimit = false)
    {
        $connection = $this->session->acquire($this->connection);
        return $connection->count($this, $useSkipAndLimit);
    }

    public function save(array $options = [])
    {
        $this->options = (new Options([
            'filter' => true,
            'observer' => true,
        ]))->merge($options);

        if ($this->options['observer']) {
            $this->schema->observe($this, function () {
                $this->doSave();
            });
        } else {
            $this->doSave();
        }

        return $this;
    }

    public function delete(array $options = [])
    {
        $this->mode = 'delete';

        $this->options = (new Options([
            'filter' => true,
            'observer' => true,
        ]))->merge($options);

        if ($this->options['observer']) {
            $this->schema->observe($this, function () {
                $this->doDelete();
            });
        } else {
            $this->doDelete();
        }

        return $this;
    }

    protected function doDelete()
    {
        $connection = $this->session->acquire($this->connection);
        return $connection->delete($this);
    }

    public function single()
    {
        [ $row ] = $this->limit(1)->all();
        return $row;
    }

    public function all()
    {
        $connection = $this->session->acquire($this->connection);

        $rows = [];
        $this->affected = $connection->load($this, function ($row) use (&$rows) {
            $rows[] = $this->schema->attach($row);
        });

        return $rows;
    }

    protected function doSave()
    {
        $connection = $this->session->acquire($this->connection);

        if ($this->mode === 'insert') {
            if ($this->getOption('filter')) {
                foreach ($this->rows as $row) {
                    $this->schema->filter($row, $this->session);
                }
            }

            $rows = new Collection();
            $this->affected = $connection->insert($this, function ($row) use ($rows) {
                $rows[] = $this->schema->attach($row);
            });

            return $rows;
        } else {
            if ($this->getOption('filter')) {
                $this->schema->filter($this->sets, $this->session, true);
            }

            $this->affected = $connection->update($this);
        }
    }

    /**
     * Getter rows
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    public function getSets()
    {
        return $this->sets;
    }

    /**
     * Getter affected
     *
     * @return int
     */
    public function getAffected()
    {
        return $this->affected;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }
}
