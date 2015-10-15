<?php

namespace Norm\Test\Sqlite;

use Norm\Test\Driver\AbstractCursorTest;
use Norm\Connection\PDOConnection;

class SqlitePDOCursorTest extends AbstractCursorTest
{
    protected $cursorClazz = 'Norm\\Cursor\\PDOCursor';

    protected $connection;

    public function getConnection()
    {
        if (is_null($this->connection)) {
            $options = array(
                'name' => 'default',
                'prefix' => 'sqlite',
                // 'database' => '/home/jafar/workspaces/php/lib/norm/test.sqlite',
                'database' => ':memory:',
                'autoddl' => 'create',
            );

            $this->connection = new PDOConnection($options);
        }

        return $this->connection;
    }
}
