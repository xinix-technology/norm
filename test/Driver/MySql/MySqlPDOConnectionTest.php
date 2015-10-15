<?php

namespace Norm\Test\Mysql;

use Norm\Test\Driver\AbstractConnectionTest;

class MySqlPDOConnectionTest extends AbstractConnectionTest
{
    protected $clazz = 'Norm\\Connection\\PDOConnection';

    protected $cursorClazz = 'Norm\\Cursor\\PDOCursor';

    public function getConnection()
    {
        $Clazz = $this->clazz;

        $options = array(
            'name' => 'default',
            'prefix' => 'mysql',
            'dbname' => 'test',
            'autoddl' => 'create',
            'username' => 'root',
            'password' => '',
        );

        $this->connection = new $Clazz($options);

        return $this->connection;
    }
}
