<?php

namespace Odan\Test;

use Odan\Database\Operator;
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
     * @covers ::prepare
     * @covers ::build
     */
    public function testDistinct()
    {
        $select = $this->select()->distinct()->columns('id')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT DISTINCT `id` FROM `test`", $select->build());
    }

    /**
     * Test
     *
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testColumns()
    {
        $select = $this->select()->columns('id', 'username', 'first_name AS firstName')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id`,`username`,`first_name` AS `firstName` FROM `test`", $select->build());

        // queries without table
        $select = $this->select()->columns(new RawExp("ISNULL(1+1)"));
        $this->assertEquals("SELECT ISNULL(1+1)", $select->build());

        $select = $this->select()->columns(new RawExp("INTERVAL(23, 1, 15, 17, 30, 44, 200)"));
        $this->assertEquals("SELECT INTERVAL(23, 1, 15, 17, 30, 44, 200)", $select->build());
    }

    /**
     * Test
     *
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testFrom()
    {
        $select = $this->select()->columns('id')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id` FROM `test`", $select->build());

        $select = $this->select()->columns('id')->from('test AS t');
        $this->assertEquals("SELECT `id` FROM `test` AS `t`", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhere()
    {
        $select = $this->select()->columns('id')->from('test')->where('id', '=', 1);
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` = '1'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '>=', 3);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` >= '3'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '>', 4);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` > '4'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<', 5);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` < '5'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<=', 6);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` <= '6'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<>', 7);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` <> '7'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '!=', 8);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` != '8'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<>', null);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IS NOT NULL", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '!=', null);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IS NOT NULL", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '=', null);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IS NULL", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'is', null);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IS NULL", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'is not', null);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IS NOT NULL", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereIn()
    {
        $select = $this->select()->columns('id')->from('test')->where('id', 'in', [1, 'a', "'b", null]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` IN ('1', 'a', '\'b', NULL)", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'not in', [2, 'a', "'b", null]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` NOT IN ('2', 'a', '\'b', NULL)", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereFunction()
    {
        $select = $this->select()->columns('id')->from('test')->where('id', 'greatest', [1, '2', "'b", null]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` = GREATEST ('1', '2', '\'b', NULL)", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'interval', [1, 2, 3]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` = INTERVAL ('1', '2', '3')", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'strcmp', ['text', "text'2"]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` = STRCMP ('text', 'text\'2')", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereBetween()
    {
        $select = $this->select()->columns('id')->from('test')->where('id', 'between', [1, 100]);
        $this->assertEquals("SELECT `id` FROM `test` WHERE `id` BETWEEN '1' AND '100'", $select->build());
    }

    /**
     * Test Simple pattern matching
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereLike()
    {
        $select = $this->select()->columns('id')->from('test')->where('first_name', 'like', "%max%");
        $this->assertEquals("SELECT `id` FROM `test` WHERE `first_name` LIKE '%max%'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('first_name', 'like', "%a'b%");
        $this->assertEquals("SELECT `id` FROM `test` WHERE `first_name` LIKE '%a\'b%'", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('first_name', 'not like', "%a'1%");
        $this->assertEquals("SELECT `id` FROM `test` WHERE `first_name` NOT LIKE '%a\'1%'", $select->build());
    }

    /**
     * Test Simple pattern matching
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereRegexp()
    {
        $select = $this->select()->from('users')->where('username', Operator::REGEXP, '^[a-d]');
        $this->assertEquals("SELECT * FROM `users` WHERE `username` REGEXP '^[a-d]'", $select->build());

        $select = $this->select()->from('users')->where('username', Operator::REGEXP, "new\\*.\\*line");
        $this->assertEquals("SELECT * FROM `users` WHERE `username` REGEXP 'new\\\\*.\\\\*line'", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereRaw()
    {
        $select = $this->select()->columns('id')->from('test')->where(new RawExp("STRCMP('text', 'text2')"));
        $this->assertEquals("SELECT `id` FROM `test` WHERE STRCMP('text', 'text2')", $select->build());

        $select = $this->select()->columns('id')->from('test')->where(new RawExp("ISNULL(1+1)"));
        $this->assertEquals("SELECT `id` FROM `test` WHERE ISNULL(1+1)", $select->build());
    }

    /**
     * Test
     *
     * @covers ::where
     * @covers ::whereColumn
     * @covers ::orWhereColumn
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testWhereColumn()
    {
        $select = $this->select()->from('users')->whereColumn('first_name', '=', 'last_name');
        $this->assertEquals("SELECT * FROM `users` WHERE `first_name` = `last_name`", $select->build());

        $select = $select->orWhereColumn('votes', '>=', 'vote_max');
        $this->assertEquals("SELECT * FROM `users` WHERE `first_name` = `last_name` OR `votes` >= `vote_max`", $select->build());

        $select = $this->select()->from('users')->whereColumn('users.email', '=', 'table2.email');
        $this->assertEquals("SELECT * FROM `users` WHERE `users`.`email` = `table2`.`email`", $select->build());

        $select = $this->select()->from('users')
            ->whereColumn('first_name', '=', 'last_name')
            ->whereColumn('updated_at', '=', 'created_at');
        $this->assertEquals("SELECT * FROM `users` WHERE `first_name` = `last_name` AND `updated_at` = `created_at`", $select->build());
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
     * @covers ::offset
     * @covers ::getLimitSql
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::getOrderBySql
     * @covers ::getGroupBySql
     * @covers \Odan\Database\Quoter::quoteByFields
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     * @covers \Odan\Database\RawExp::__construct
     * @covers \Odan\Database\RawExp::getValue
     * @covers \Odan\Database\RawExp::__toString
     */
    public function testWhereClosure()
    {
        $select = $this->select()
            ->distinct()
            ->columns('id', 'username')
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
                $query->where('t2.field', '=', '1');
                $query->where('t2.field2', '>', '1');
            })
            ->orWhere(function (SelectQuery $query) {
                $query->where('t.a', '<>', '2');
                $query->where('t.b', '=', null);
                $query->where('t.c', '>', '5');
                $query->orWhere(function (SelectQuery $query) {
                    $query->where(new RawExp('a.id = b.id'));
                    $query->orWhere(new RawExp('c.id = u.id'));
                });
            })
            ->where('u.id', '>=', 0)
            ->orWhere('u.id', 'between', [100, 200])
            ->groupBy('id', 'username ASC')
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
            ->groupBy('id', 'username')
            ->orderBy('id ASC', 'username DESC')
            ->limit(10)
            ->offset(0);

        $sql = $select->build();
        $expected = 'e0d4b71b69514d735722ff18c30a030166bd7eaa';
        $actual = sha1($sql);
        if ($expected != $actual) {
            echo "\nSQL: $sql\n";
            file_put_contents(__DIR__ . '/debug.sql', $sql);
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test
     *
     * @covers ::join
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testJoin()
    {
        $select = $this->select()
            ->columns('id')
            ->from('test')
            ->join('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id` FROM `test` INNER JOIN `users` `u` ON `u`.`id` = `test`.`user_id`", $select->build());

        $select->join('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT `id` FROM `test` INNER JOIN `users` `u` ON `u`.`id` = `test`.`user_id` INNER JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`";
        $this->assertEquals($expected, $select->build());
    }

    /**
     * Test
     *
     * @covers ::leftJoin
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testLeftJoin()
    {
        $select = $this->select()
            ->columns('id')
            ->from('test')
            ->leftJoin('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id` FROM `test` LEFT JOIN `users` `u` ON `u`.`id` = `test`.`user_id`", $select->build());

        $select->leftJoin('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT `id` FROM `test` LEFT JOIN `users` `u` ON `u`.`id` = `test`.`user_id` LEFT JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`";
        $this->assertEquals($expected, $select->build());
    }

    /**
     * Test
     *
     * @covers ::crossJoin
     * @covers ::getJoinSql
     * @covers ::leftJoin
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testCrossJoin()
    {
        $select = $this->select()
            ->columns('id')
            ->from('test')
            ->crossJoin('users u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertEquals("SELECT `id` FROM `test` CROSS JOIN `users` `u` ON `u`.`id` = `test`.`user_id`", $select->build());

        $select->crossJoin('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = "SELECT `id` FROM `test` CROSS JOIN `users` `u` ON `u`.`id` = `test`.`user_id` CROSS JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`";
        $this->assertEquals($expected, $select->build());

        $select->leftJoin('table3 AS t3', 't3.id', '=', 'test.user_id');
        $expected = "SELECT `id` FROM `test` CROSS JOIN `users` `u` ON `u`.`id` = `test`.`user_id` CROSS JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id` LEFT JOIN `table3` AS `t3` ON `t3`.`id` = `test`.`user_id`";
        $this->assertEquals($expected, $select->build());
    }

    /**
     * Test
     *
     * @covers ::limit
     * @covers ::getLimitSql
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testLimit()
    {
        $select = $this->select()->columns('id')->from('test')->limit(10);
        $this->assertEquals("SELECT `id` FROM `test` LIMIT 10", $select->build());
    }
}
