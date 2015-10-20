<?php

namespace Norm\Test;

use stdClass;
use Exception;
use Norm\Connection;
use Norm\Collection;
use Norm\Cursor;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function mockConnectionWithRows($rows)
    {
        $connection = $this->getMock(Connection::class, [
            'cursorRead',
            'cursorFetch',
            'cursorSize',
            'cursorDistinct',
            'persist',
            'remove'
        ]);
        $connection->method('cursorRead')
            ->will($this->returnCallback(function ($context, $position) {
                if (isset($context[$position])) {
                    return $context[$position];
                }
            }));
        $connection->method('cursorFetch')
            ->will($this->returnCallback(function ($cursor) use ($rows) {
                return $rows;
            }));
        $connection->method('cursorDistinct')
            ->will($this->returnCallback(function ($cursor, $key) use ($rows) {
                $result = [];
                foreach ($rows as $k => $v) {
                    if (!in_array($v[$key], $result)) {
                        $result[] = $v[$key];
                    }
                }
                return $result;
            }));
        $connection->method('cursorSize')
            ->will($this->returnCallback(function ($cursor, $respectLimitSkip = false) use ($rows) {
                return count($rows);
            }));

        return $connection;
    }

    public function testGetRaw()
    {
        $connection  = $this->mockConnectionWithRows([]);
        $this->assertNull($connection->getRaw());
    }

    public function testUnmarshall()
    {
        $connection  = $this->mockConnectionWithRows([]);
        $arr = $connection->unmarshall([
            'id' => 10,
            'regular' => 'yes',
            '_hidden' => 'shy',
        ]);
        $this->assertEquals(10, $arr['$id']);
        $this->assertEquals('yes', $arr['regular']);
        $this->assertEquals('shy', $arr['$hidden']);

        try {
            $connection->unmarshall('non-array');
            throw new Exception('Error not thrown with scalar parameter');
        } catch (Exception $e) {
        }

        try {
            $connection->unmarshall(new stdClass());
            throw new Exception('Error not thrown with object parameter');
        } catch (Exception $e) {
        }
    }

    public function testMarshall()
    {
        $connection  = $this->mockConnectionWithRows([]);
        $arr = $connection->marshall([
            '$id' => 10,
            'regular' => 'yes',
            '$hidden' => 'shy',
        ]);
        $this->assertFalse(isset($arr['id']));
        $this->assertEquals('yes', $arr['regular']);
        $this->assertEquals('shy', $arr['_hidden']);
    }
}
