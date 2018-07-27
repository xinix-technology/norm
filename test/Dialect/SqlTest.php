<?php
namespace Norm\Test\Dialect;

use Norm\Dialect\Sql;
use PHPUnit\Framework\TestCase;
use Norm\Exception\NormException;

class SqlTest extends TestCase
{
    public function testEsc()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals($dialect->esc('foo'), 'foo');
    }

    public function testGrammarInsert()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarInsert('foo', ['foo' => 'oof', 'bar' => 'rab', ]),
            'INSERT INTO foo (foo, bar) VALUES (:foo, :bar)'
        );
    }

    public function testGrammarSelect()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarSelect('foo'),
            'SELECT * FROM foo'
        );
    }

    public function testGrammarUpdate()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarUpdate('foo', ['foo' => 'oof', 'bar' => 'rab', ]),
            'UPDATE foo SET foo = :foo, bar = :bar WHERE id = :id'
        );
    }

    public function testGrammarDelete()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarDelete('foo', ['id' => 10 ]),
            'DELETE FROM foo WHERE id = :id'
        );
    }

    public function testGrammarWhere()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarWhere([]),
            ''
        );

        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarWhere(['id' => 10 ]),
            'WHERE id = :id'
        );

        try {
            $dialect->grammarWhere(['id!oops' => 10 ]);
            $this->fail('Must not here');
        } catch (NormException $e) {
            if (strpos($e->getMessage(), 'Operator') !== 0) {
                throw $e;
            }
        }
    }

    public function testGrammarOrder()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarOrder(['id' => 1 ]),
            'ORDER BY id ASC'
        );

        $this->assertEquals(
            $dialect->grammarOrder([]),
            ''
        );
    }

    public function testGrammarLimit()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarCount('foo'),
            'SELECT COUNT(*) AS count FROM foo'
        );
    }

    public function testGrammarCount()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarLimit(10, 20),
            'LIMIT 10, 20'
        );
    }

    public function testGrammarDistinct()
    {
        $dialect = $this->getMockForAbstractClass(Sql::class);
        $this->assertEquals(
            $dialect->grammarDistinct('foo', 'bar', ['baz' => 1]),
            'SELECT DISTINCT bar FROM foo WHERE baz = :baz'
        );
    }
}
