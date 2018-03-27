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

    public function testQueryIn()
    {
        $collection = $this->connection->factory($this->collection->getClass())->find(array(
            'first_name!in' => array('wahyu', 'farid')
        ));

        $this->assertEquals($collection->count(true), 3);

        $model = $collection->getNext();

        $this->assertEquals($model->first_name, 'farid');
        $this->assertEquals($model->last_name, 'hidayat');

        $model = $collection->getNext();

        $this->assertNotEquals($model->first_name, 'farid');
        $this->assertNotEquals($model->last_name, 'hidayat');

        $this->assertEquals($model->first_name, 'wahyu');
        $this->assertEquals($model->last_name, 'pribadi');

        $model = $collection->getNext();

        $this->assertEquals($model->first_name, 'wahyu');
        $this->assertNotEquals($model->last_name, 'pribadi');

        $this->assertEquals($model->first_name, 'wahyu');
        $this->assertEquals($model->last_name, 'taufik');

        $this->assertTrue(is_null($collection->getNext()));
    }
}
