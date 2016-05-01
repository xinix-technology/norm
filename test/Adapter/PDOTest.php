<?php
namespace Norm\Test\Adapter;

use Norm\Adapter\PDO;
use Norm\Collection;
use Norm\Cursor;
use Norm\Exception\NormException;
use PHPUnit_Framework_TestCase;
use PDO as ThePDO;

class PDOTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->connection = new PDO(null, 'main', [
            'dsn' => 'sqlite::memory:'
        ]);

        $db = $this->connection->getContext();
        $db->exec('CREATE TABLE IF NOT EXISTS foo (
            id INTEGER PRIMARY KEY,
            foo INTEGER,
            bar TEXT)');

        $ps = $db->prepare('INSERT INTO foo(foo, bar) VALUES (:foo, :bar)');
        for($i = 0; $i < 3; $i++) {
            $ps->execute([
                'foo' => $i + 100,
                'bar' => 'preload-'.$i,
            ]);
        }
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(ThePDO::class, $this->connection->getContext());

        try {
            new PDO();
            $this->fail('Must not here');
        } catch (NormException $e) {
            if ($e->getMessage() !== 'DSN is required') {
                throw $e;
            }
        }
    }

    public function testPersist()
    {
        $db = $this->connection->getContext();

        $result = $this->connection->persist('foo', [ 'foo' => 1, 'bar' => 'bar1' ]);
        $this->connection->persist('foo', [ 'foo' => 2, 'bar' => 'bar2' ]);
        $this->connection->persist('foo', [ 'foo' => 3, 'bar' => 'bar3' ]);

        $count = 0;
        foreach($db->query('SELECT * FROM foo') as $row) {
            $count++;
        }
        $this->assertEquals($count, 6);

        $result['bar'] = 'baz';
        $this->connection->persist('foo', $result);

        $this->assertEquals($db->query('SELECT * FROM foo WHERE foo = 1')->fetch()['bar'], 'baz');


        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $this->connection->remove($cursor);

        $this->assertEquals(count($db->query('SELECT * FROM foo')->fetchAll()), 0);
    }

    public function testRead()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $entry1 = $this->connection->read($cursor);
        $cursor->next();
        $cursor->next();
        $cursor->next();
        $cursor->rewind();
        $entry2 = $this->connection->read($cursor);
        $this->assertEquals($entry1, $entry2);
    }

    public function testSize()
    {
        $cursor = new Cursor($this->getMock(Collection::class, null, [$this->connection, 'Foo']));
        $this->assertEquals($this->connection->size($cursor), 3);
    }

    public function testDistinct()
    {
        $collection = $this->getMock(Collection::class, null, [$this->connection, 'Foo']);
        $this->assertEquals(count($this->connection->distinct(new Cursor($collection), 'foo')), 3);
    }


    // protected $repository;

    // public function setUp()
    // {

    //     $this->repository = new Repository([
    //         'connections' => [
    //             [ PDO::class, [
    //                 'id' => 'sqlite',
    //                 'options' => [
    //                     'dsn' => 'sqlite::memory:',
    //                 ]
    //             ]]
    //         ]
    //     ]);

    //     $columns = [
    //         'id INTEGER PRIMARY KEY',
    //         'fname TEXT',
    //         'lname TEXT',
    //     ];
    //     $sql = sprintf('CREATE TABLE foo (%s)', implode(', ', $columns));
    //     try {
    //         $raw = $this->repository->getConnection('sqlite')->getContext();
    //     } catch (\Exception $e) {
    //         $this->markTestSkipped($e->getMessage());
    //     }
    //     $raw->exec($sql);

    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set(['fname' => 'Jane', 'lname' => 'Doe']);
    //     $model->save();
    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set(['fname' => 'Ganesha', 'lname' => 'M']);
    //     $model->save();
    // }

    // public function testSearch()
    // {
    //     $cursor = $this->repository->factory('Foo')->find();

    //     $i = 0;
    //     foreach ($cursor as $row) {
    //         $i++;
    //     }
    //     $this->assertEquals(2, $i);
    //     $this->assertInstanceOf(Cursor::class, $cursor);
    // }

    // public function testCreate()
    // {
    //     $model = $this->repository->factory('Foo')->newInstance();
    //     $model->set([
    //         'fname' => 'John',
    //         'lname' => 'Doe',
    //     ]);
    //     $model->save();

    //     $statement = $this->repository->getConnection()->getContext()
    //         ->prepare('SELECT * FROM foo WHERE id = ?');
    //     $statement->execute([$model['$id']]);
    //     $expected = $statement->fetch(\PDO::FETCH_ASSOC);

    //     $this->assertEquals(
    //         $expected['id'],
    //         $model['$id']
    //     );
    // }

    // public function testUpdate()
    // {
    //     $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
    //     $model['fname'] = 'Rob';
    //     $model->save();

    //     $statement = $this->repository->getConnection()->getContext()
    //         ->prepare('SELECT * FROM foo WHERE id = ?');
    //     $statement->execute([$model['$id']]);
    //     $expected = $statement->fetch(\PDO::FETCH_ASSOC);

    //     $this->assertEquals('Rob', $expected['fname']);
    // }

    // public function testDelete()
    // {
    //     $model = $this->repository->factory('Foo')->findOne(['fname' => 'Ganesha']);
    //     $model->remove();

    //     $statement = $this->repository->getConnection()->getContext()
    //         ->prepare('SELECT COUNT(*) FROM foo');
    //     $statement->execute();
    //     $count = $statement->fetch()[0];

    //     $this->assertEquals(1, $count);
    // }
}
