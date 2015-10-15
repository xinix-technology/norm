<?php

namespace Norm\Test\MySql;

use Norm\Test\Driver\AbstractCursorTest;
use Norm\Connection\PDOConnection;

class MySqlPDOCursorTest extends AbstractCursorTest
{
    protected $cursorClazz = 'Norm\\Cursor\\PDOCursor';

    protected $connection;

    public function getConnection()
    {
        if (is_null($this->connection)) {
            $options = array(
                'name' => 'default',
                'prefix' => 'mysql',
                'dbname' => 'test',
                'autoddl' => 'create',
            );

            $this->connection = new PDOConnection($options);
        }

        return $this->connection;
    }
}
