<?php

namespace Norm\Test\Driver;

use Norm\Collection;
use Norm\Schema\String;

abstract class AbstractCursorTest extends \PHPUnit_Framework_TestCase
{

    protected $cursorClazz;

    protected $connectionClazz;

    protected $collection;

    protected $fixtures = array(
        array(
            'first_name' => 'putra',
            'last_name' => 'pramana',
        ),
        array(
            'first_name' => 'farid',
            'last_name' => 'hidayat',
        ),
        array(
            'first_name' => 'pendi',
            'last_name' => 'setiawan',
        ),
    );

    abstract public function getConnection();

    public function setUp()
    {
        $connection = $this->getConnection();

        $options = array(
            'name' => 'test_user',
            'schema' => array(
                'first_name' => String::create('first_name'),
                'last_name' => String::create('last_name'),
            ),
            'connection' => $connection,
        );


        $this->collection = new Collection($options);

        $connection->factory($this->collection);
        $connection->remove('test_user');

        foreach ($this->fixtures as $row) {
            $model = $this->collection->newInstance();
            $model->set($row);
            $model->save();
        }
    }

    public function testConstructNoCriteria()
    {
        if (empty($this->cursorClazz)) {
            throw new \Exception('Test::cursorClazz undefined.');
        }

        $CursorClazz = $this->cursorClazz;

        // must not return error
        $cursor = new $CursorClazz($this->collection);
    }

    public function testConstructWithCriteria()
    {
        $CursorClazz = $this->cursorClazz;

        // must not return error
        $cursor = new $CursorClazz($this->collection, array(
            'first_name' => 'putra'
        ));
    }

    public function testGetNext()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $model = $cursor->getNext();

        $message = 'Cursor::getNext() expected return instance of model';
        $this->assertInstanceOf('Norm\\Model', $model, $message);

        $cursor = new $CursorClazz($this->collection, array('first_name' => '*ghost*'));
        $model = $cursor->getNext();

        $message = 'Cursor::getNext() expected return null';
        $this->assertNull($model, $message);
    }

    public function testJsonSerialize()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $result = $cursor->jsonSerialize();

        $message = 'Cursor::jsonSerialize() expected return array';
        $this->assertTrue(is_array($result), $message);
    }

    public function testLimit()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $result = $cursor->limit(1);

        $limit = $cursor->limit();

        $message = 'Cursor::limit() expected count() to be 3';
        $this->assertEquals(3, $cursor->count(), $message);

        $message = 'Cursor::limit() expected count(true) to be 1';
        $this->assertEquals(1, $cursor->count(true), $message);

        $message = 'Cursor::limit() expected return chainable object';
        $this->assertEquals($cursor, $result, $message);

        $message = 'Cursor::limit() with no arg expected return limit';
        $this->assertEquals(1, $limit, $message);
    }

    public function testSkip()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $result = $cursor->skip(1);

        $skip = $cursor->skip();

        $message = 'Cursor::skip() expected count() to be 3';
        $this->assertEquals(3, $cursor->count(), $message);

        $message = 'Cursor::skip() expected count(true) to be 1';
        $this->assertEquals(2, $cursor->count(true), $message);

        $message = 'Cursor::skip() expected return chainable object';
        $this->assertEquals($cursor, $result, $message);

        $message = 'Cursor::skip() with no arg expected return skip';
        $this->assertEquals(1, $skip, $message);
    }

    public function testSort()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $result = $cursor->sort(array('first_name' => 1));

        $name = null;
        foreach ($this->fixtures as $row) {
            if (is_null($name)) {
                $name = $row['first_name'];
            } elseif (strcasecmp($name, $row['first_name']) > 0) {
                $name = $row['first_name'];
            }
        }

        $cursor->rewind();
        $model = $cursor->current();

        $message = 'Cursor::sort() expected first model to be '.$name;
        $this->assertEquals($name, $model['first_name'], $message);

        $message = 'Cursor::sort() expected return chainable object';
        $this->assertEquals($cursor, $result, $message);
    }

    public function testMatch()
    {
        $CursorClazz = $this->cursorClazz;

        $fixture = $this->fixtures;

        $q = substr($fixture[0]['first_name'], 1) ;

        $name;
        foreach ($fixture as $row) {
            if (preg_match('/'.$q.'/i', $row['first_name'])) {
                $name = $row['first_name'];
            }
        }


        $cursor = new $CursorClazz($this->collection);
        $cursor->match($q);

        $model = $cursor->getNext();

        $message = 'Cursor::match() expected first model to be '.$name;
        $this->assertEquals($name, $model['first_name'], $message);
    }

    public function testToArray()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $arr = $cursor->toArray();

        $message = 'Cursor::toArray() expected return array of models';
        $this->assertTrue(is_array($arr), $message);
        $this->assertInstanceOf('Norm\\Model', $arr[0], $message);

        $cursor = new $CursorClazz($this->collection);
        $arr = $cursor->toArray(true);

        $message = 'Cursor::toArray(true) expected return array of assoc array';
        $this->assertTrue(is_array($arr), $message);
        $this->assertTrue(is_array($arr[0]), $message);
    }

    public function testCount()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);
        $cursor->skip(1);

        $count = count($this->fixtures);

        $message = 'Cursor::count() expected return count of all table rows';
        $this->assertEquals($count, $cursor->count());

        $message = 'Cursor::count() expected return count found only';
        $this->assertEquals($count - 1, $cursor->count(true));
    }

    public function testTranslateCriteria()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);

        $message = 'Cursor::translateCriteria() should result';
        $result = $cursor->translateCriteria(array('visible' => 'something'));
        $this->assertNotNull('visible', $result, $message);
    }

    public function testCurrent()
    {
        $CursorClazz = $this->cursorClazz;

        $cursor = new $CursorClazz($this->collection);

        $model = $cursor->current();
        $this->assertNull($model, 'Cursor::current() will return null before next()');

        $cursor->next();
        $model = $cursor->current();
        $this->assertInstanceOf('Norm\\Model', $model, 'Cursor::current() will return model after next()');

        $model2 = $cursor->current();
        $this->assertEquals($model2, $model, 'Cursor::current() will return the same model');
    }

    public function testNext()
    {
        $CursorClazz = $this->cursorClazz;
        $cursor = new $CursorClazz($this->collection);

        $beforeNext = $cursor->current();
        $cursor->next();
        $afterNext = $cursor->current();

        $this->assertNull($beforeNext, 'Cursor::next() will move cursor to the first document');
        $this->assertInstanceOf('Norm\\Model', $afterNext, 'Cursor::next() will move cursor to the first document');
    }

    public function testKey()
    {
        $CursorClazz = $this->cursorClazz;
        $cursor = new $CursorClazz($this->collection);

        $key1 = $cursor->key();
        $cursor->next();
        $key2 = $cursor->key();

        $this->assertNull($key1, 'Cursor::key() return null before next()');
        $this->assertNotNull($key2, 'Cursor::key() will return document id after next()');
    }

    public function testValid()
    {
        $CursorClazz = $this->cursorClazz;
        $cursor = new $CursorClazz($this->collection);

        $valid1 = $cursor->valid();
        $cursor->next();
        $valid2 = $cursor->valid();

        $this->assertFalse($valid1, 'Cursor::valid() return null before next()');
        $this->assertTrue($valid2, 'Cursor::valid() will return document id after next()');
    }

    public function testRewind()
    {
        $CursorClazz = $this->cursorClazz;
        $cursor = new $CursorClazz($this->collection);

        $cursor->next();
        $cursor->next();
        $cursor->rewind();

        $model = $cursor->current();
        $this->assertEquals('putra', $model['first_name'], 'Cursor::rewind() will reset cursor');
    }

    // public function testDistinct()
    // {
    //     $CursorClazz = $this->cursorClazz;
    //     $cursor = new $CursorClazz($this->collection);

    //     throw new \Exception('Unfinished yet!');
    // }
}
