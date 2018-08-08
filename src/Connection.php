<?php

namespace Norm;

/**
 * Base class for connection instance
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Connection
{
    /**
     * @var bool
     */
    protected $hasTx = false;

    protected $primaryKey = 'id';

    public function begin()
    {
        if ($this->hasTx) {
            return;
        }

        $this->execBegin();

        $this->hasTx = true;
    }

    public function commit()
    {
        if (!$this->hasTx) {
            return;
        }

        $this->execCommit();

        $this->hasTx = false;
    }

    public function rollback()
    {
        if (!$this->hasTx) {
            return;
        }

        $this->execRollback();

        $this->hasTx = false;
    }

    abstract protected function execBegin();

    abstract protected function execCommit();

    abstract protected function execRollback();

    abstract public function insert(Query $query, callable $callback);

    abstract public function update(Query $query);

    abstract public function delete(Query $query);

    abstract public function count(Query $query, bool $useSkipAndLimit = false);
}
