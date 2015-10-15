<?php

namespace Norm\Test\Sqlite;

use Norm\Test\Driver\AbstractConnectionTest;

class SqlitePDOConnectionTest extends AbstractConnectionTest
{
    protected $clazz = 'Norm\\Connection\\PDOConnection';

    protected $cursorClazz = 'Norm\\Cursor\\PDOCursor';

    public function getConnection()
    {
        $Clazz = $this->clazz;

        $options = array(
            'name' => 'default',
            'prefix' => 'sqlite',
            'database' => ':memory:',
            'autoddl' => 'create',
        );

        $this->connection = new $Clazz($options);

        return $this->connection;
    }
}
