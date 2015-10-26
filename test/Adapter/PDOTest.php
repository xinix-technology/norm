<?php

namespace Norm\Test\Adapter;

use Norm\Adapter\PDO;
use Norm\Norm as TheNorm;
use Norm\Cursor;
use PHPUnit_Framework_TestCase;

class PDOTest extends PHPUnit_Framework_TestCase
{
    protected $norm;

    public function setUp()
    {

        $this->norm = new TheNorm([
            'connections' => [
                'sqlite' => [
                    'class' => PDO::class,
                    'config' => [
                        'dsn' => 'sqlite::memory:',
                    ]
                ]
            ]
        ]);

        $columns = [
            'id INTEGER PRIMARY KEY',
            'fname TEXT',
            'lname TEXT',
        ];
        $sql = sprintf('CREATE TABLE foo (%s)', implode(', ', $columns));
        $raw = $this->norm->getConnection('sqlite')->getRaw();
        $raw->exec($sql);

        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
        $model->save();
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
        $model->save();
    }

    public function testSearch()
    {
        $cursor = $this->norm->factory('Foo')->find();

        $i = 0;
        foreach ($cursor as $row) {
            $i++;
        }
        $this->assertEquals(2, $i);
        $this->assertInstanceOf(Cursor::class, $cursor);
    }

    public function testCreate()
    {
        $model = $this->norm->factory('Foo')->newInstance();
        $model->set([
            'fname' => 'John',
            'lname' => 'Doe',
        ]);
        $model->save();

        $statement = $this->norm->getConnection()->getRaw()
            ->prepare('SELECT * FROM foo WHERE id = ?');
        $statement->execute([$model['$id']]);
        $expected = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals(
            $expected['id'],
            $model['$id']
        );
    }

    public function testRead()
    {
        $this->testCreate();

        $model = $this->norm->factory('Foo')->findOne(['fname' => 'John']);
        $this->assertEquals('Doe', $model['lname']);
    }

    public function testUpdate()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model['fname'] = 'Rob';
        $model->save();

        $statement = $this->norm->getConnection()->getRaw()
            ->prepare('SELECT * FROM foo WHERE id = ?');
        $statement->execute([$model['$id']]);
        $expected = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('Rob', $expected['fname']);
    }

    public function testDelete()
    {
        $model = $this->norm->factory('Foo')->findOne(['fname' => 'Ganesha']);
        $model->remove();

        $statement = $this->norm->getConnection()->getRaw()
            ->prepare('SELECT COUNT(*) FROM foo');
        $statement->execute();
        $count = $statement->fetch()[0];

        $this->assertEquals(1, $count);
    }
}
