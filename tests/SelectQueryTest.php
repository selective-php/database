<?php

namespace Odan\Test;

use Odan\Database\RawExp;
use Odan\Database\SelectQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\SelectQuery
 */
class SelectQueryTest extends BaseTest
{

    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * Test create object.
     *
     * @return void
     * @covers ::__construct
     */
    public function testInstance()
    {
        $this->assertInstanceOf(SelectQuery::class, $this->select());
    }

    /**
     * @return SelectQuery
     */
    protected function select()
    {
        return new SelectQuery($this->getConnection());
    }

    /**
     * Test
     *
     * @covers ::distinct
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testDistinct()
    {
        $select = $this->select()->distinct()->columns(['id'])->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT DISTINCT id FROM test", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testColumns()
    {
        $select = $this->select()->columns(['id', 'username', 'first_name AS firstName'])->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id,username,first_name AS firstName FROM test", $select->getSql());

        // queries without table
        $select = $this->select()->columns([new RawExp("ISNULL(1+1)")]);
        $this->assertEquals("SELECT ISNULL(1+1)", $select->getSql());

        $select = $this->select()->columns([new RawExp("INTERVAL(23, 1, 15, 17, 30, 44, 200)")]);
        $this->assertEquals("SELECT INTERVAL(23, 1, 15, 17, 30, 44, 200)", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testFrom()
    {
        $select = $this->select()->columns(['id'])->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id FROM test", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test AS t');
        $this->assertEquals("SELECT id FROM test AS t", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhere()
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', '=', 1);
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id FROM test WHERE id = '1'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '>=', 3);
        $this->assertEquals("SELECT id FROM test WHERE id >= '3'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '>', 4);
        $this->assertEquals("SELECT id FROM test WHERE id > '4'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<', 5);
        $this->assertEquals("SELECT id FROM test WHERE id < '5'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<=', 6);
        $this->assertEquals("SELECT id FROM test WHERE id <= '6'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<>', 7);
        $this->assertEquals("SELECT id FROM test WHERE id <> '7'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '!=', 8);
        $this->assertEquals("SELECT id FROM test WHERE id != '8'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<>', null);
        $this->assertEquals("SELECT id FROM test WHERE id IS NOT NULL", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '!=', null);
        $this->assertEquals("SELECT id FROM test WHERE id IS NOT NULL", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '=', null);
        $this->assertEquals("SELECT id FROM test WHERE id IS NULL", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'is', null);
        $this->assertEquals("SELECT id FROM test WHERE id IS NULL", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'is not', null);
        $this->assertEquals("SELECT id FROM test WHERE id IS NOT NULL", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhereIn()
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'in', [1, 'a', "'b", null]);
        $this->assertEquals("SELECT id FROM test WHERE id IN ('1', 'a', '\'b', NULL)", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'not in', [2, 'a', "'b", null]);
        $this->assertEquals("SELECT id FROM test WHERE id NOT IN ('2', 'a', '\'b', NULL)", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhereFunction()
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'greatest', [1, '2', "'b", null]);
        $this->assertEquals("SELECT id FROM test WHERE id = GREATEST ('1', '2', '\'b', NULL)", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'interval', [1, 2, 3]);
        $this->assertEquals("SELECT id FROM test WHERE id = INTERVAL ('1', '2', '3')", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'strcmp', ['text', "text'2"]);
        $this->assertEquals("SELECT id FROM test WHERE id = STRCMP ('text', 'text\'2')", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhereBetween()
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'between', [1, 100]);
        $this->assertEquals("SELECT id FROM test WHERE id BETWEEN '1' AND '100'", $select->getSql());
    }

    /**
     * Test Simple pattern matching
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhereLike()
    {
        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'like', "%max%");
        $this->assertEquals("SELECT id FROM test WHERE first_name LIKE '%max%'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'like', "%a'b%");
        $this->assertEquals("SELECT id FROM test WHERE first_name LIKE '%a\'b%'", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'not like', "%a'1%");
        $this->assertEquals("SELECT id FROM test WHERE first_name NOT LIKE '%a\'1%'", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testWhereRaw()
    {
        $select = $this->select()->columns(['id'])->from('test')->where(new RawExp("STRCMP('text', 'text2')"));
        $this->assertEquals("SELECT id FROM test WHERE STRCMP('text', 'text2')", $select->getSql());

        $select = $this->select()->columns(['id'])->from('test')->where(new RawExp("ISNULL(1+1)"));
        $this->assertEquals("SELECT id FROM test WHERE ISNULL(1+1)", $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::orWhere
     * @covers ::having
     * @covers ::orHaving
     * @covers ::groupBy
     * @covers ::orderBy
     * @covers ::limit
     * @covers ::getLimitSql
     * @covers ::addClauseCondClosure
     * @covers ::getRightFieldValue
     * @covers ::getWhereSql
     * @covers ::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     * @covers \Odan\Database\RawExp::__construct
     * @covers \Odan\Database\RawExp::getValue
     * @covers \Odan\Database\RawExp::__toString
     */
    public function testWhereClosure()
    {
        $select = $this->select()
            ->distinct()
            ->columns(['id', 'username'])
            ->from('users u')
            ->join('customers c', 'c.created_by', '=', 'u.id')
            ->leftJoin('articles a', 'a.created_by', '=', 'u.id')
            ->where('u.id', '>=', 1)
            ->where('u.deleted', '=', 0)
            ->orWhere('u.username', 'like', "%a'a%")
            ->orWhere('u.username', 'not like', "%a'b%")
            ->orWhere('u.id', 'in', [1, 2, 3])
            ->orWhere('u.id', 'not in', [4, 5, null])
            ->orWhere('u.id', '=', null)
            ->orWhere('u.id', '!=', null)
            ->where(function (SelectQuery $query) {
                $query->where('1', '=', '1');
                $query->where('2', '>', '1');
            })
            ->orWhere(function (SelectQuery $query) {
                $query->where('1', '<>', '2');
                $query->where('2', '=', null);
                $query->where('3', '>', '5');
                $query->orWhere(function (SelectQuery $query) {
                    $query->where(new RawExp('a.id = b.id'));
                    $query->orWhere(new RawExp('c.id = u.id'));
                });
            })
            ->where('u.id', '>=', 0)
            ->orWhere('u.id', 'between', [100, 200])
            ->groupBy(['id', 'username ASC'])
            ->having('u.username', '=', '1')
            ->having('u.username', '=', '2')
            ->having(function (SelectQuery $query) {
                $query->having('x', '<>', '2');
                $query->having('y', '=', null);
                $query->having('z', '<>', '5');
                $query->orHaving(function (SelectQuery $query) {
                    $query->having(new RawExp('a.id = b.id'));
                    $query->orHaving(new RawExp('c.id = u.id'));
                });
            })
            ->orderBy(['id ASC', 'username DESC'])
            ->limit(0, 10);

        $sql = $select->getSql();
        $expected = '9348581716202dd120bf5a9db6ccd7563b24ab8c';
        $actual = sha1($sql);
        if ($expected != $actual) {
            echo "\nSQL: $sql\n";
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test
     *
     * @covers ::join
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testJoin()
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->join('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id FROM test INNER JOIN users u ON u.id = test.user_id", $select->getSql());

        $select->join('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT id FROM test INNER JOIN users u ON u.id = test.user_id INNER JOIN table2 AS t2 ON t2.id = test.user_id";
        $this->assertEquals($expected, $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::leftJoin
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testLeftJoin()
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->leftJoin('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id FROM test LEFT JOIN users u ON u.id = test.user_id", $select->getSql());

        $select->leftJoin('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT id FROM test LEFT JOIN users u ON u.id = test.user_id LEFT JOIN table2 AS t2 ON t2.id = test.user_id";
        $this->assertEquals($expected, $select->getSql());
    }

    /**
     * Test
     *
     * @covers ::crossJoin
     * @covers ::getJoinSql
     * @covers ::leftJoin
     * @covers ::from
     * @covers ::getStatement
     * @covers ::getSql
     */
    public function testCrossJoin()
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->crossJoin('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->getStatement());
        $this->assertEquals("SELECT id FROM test CROSS JOIN users u ON u.id = test.user_id", $select->getSql());

        $select->crossJoin('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT id FROM test CROSS JOIN users u ON u.id = test.user_id CROSS JOIN table2 AS t2 ON t2.id = test.user_id";
        $this->assertEquals($expected, $select->getSql());

        $select->leftJoin('table3 AS t3', 't3.id', '=', 'test.user_id');
        $expected = "SELECT id FROM test CROSS JOIN users u ON u.id = test.user_id CROSS JOIN table2 AS t2 ON t2.id = test.user_id LEFT JOIN table3 AS t3 ON t3.id = test.user_id";
        $this->assertEquals($expected, $select->getSql());
    }
}
