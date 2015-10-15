<?php

namespace Norm\Test\Mongo;

use Norm\Test\Driver\AbstractCursorTest;
use Norm\Connection\MongoConnection;
use Norm\Cursor\MongoCursor;

class MongoCursorTest extends AbstractCursorTest
{
    protected $cursorClazz = 'Norm\\Cursor\\MongoCursor';

    protected $connection;

    public function getConnection()
    {
        if (is_null($this->connection)) {

            $options = array(
                'name' => 'default',
                'database' => 'test_connection',
            );

            $this->connection = new MongoConnection($options);
        }

        return $this->connection;
    }

    public function testTranslateCriteria()
    {
        parent::testTranslateCriteria();

        $cursor = new MongoCursor($this->collection);

        $message = 'Cursor::translateCriteria() should convert $prefixed to _prefixed field';
        $result = $cursor->translateCriteria(array('$hidden' => 'something'));
        $this->assertArrayHasKey('_hidden', $result, $message);

        $message = 'Cursor::translateCriteria() should leave intact visible field';
        $result = $cursor->translateCriteria(array('visible' => 'something'));
        $this->assertArrayHasKey('visible', $result, $message);

        $message = 'Cursor::translateCriteria() has AND operator';
        $result = $cursor->translateCriteria(array(
            '!and' => array(
                array( 'one' => 1 ),
                array( 'two' => 2 )
            )
        ));

        $message = 'Cursor::translateCriteria() has OR operator';
        $result = $cursor->translateCriteria(array(
            '!or' => array(
                array( 'one' => 1 ),
                array( 'two' => 2 )
            )
        ));

        $message = 'Cursor::translateCriteria() has AND operator';
        $result = $cursor->translateCriteria(array(
            '!or' => array(
                array( 'one' => 1 ),
                array( 'two' => 2 )
            )
        ));
    }
}
