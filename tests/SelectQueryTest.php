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
     * Test.
     *
     * @covers ::distinct
     * @covers ::distinctRow
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testDistinct()
    {
        $select = $this->select()->distinct()->columns('id')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT DISTINCT `id` FROM `test`;', $select->build());

        $select = $this->select()->distinctRow()->columns('id')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT DISTINCTROW `id` FROM `test`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::straightJoin
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testStraightJoin()
    {
        $select = $this->select()->straightJoin()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT STRAIGHT_JOIN * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::highPriority
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testHighPriority()
    {
        $select = $this->select()->highPriority()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT HIGH_PRIORITY * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::smallResult
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testSmallResult()
    {
        $select = $this->select()->smallResult()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_SMALL_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::bigResult
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testBigResult()
    {
        $select = $this->select()->bigResult()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_BIG_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::bufferResult
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testBufferResult()
    {
        $select = $this->select()->bufferResult()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_BUFFER_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::calcFoundRows
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testCalcFoundRows()
    {
        $select = $this->select()->calcFoundRows()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_CALC_FOUND_ROWS * FROM `users`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::columns
     * @covers ::getColumnsSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testColumns()
    {
        $select = $this->select()->from('users');
        $this->assertSame('SELECT * FROM `users`;', $select->build());

        $select = $this->select()->columns('id', 'username', 'first_name AS firstName')->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT `id`,`username`,`first_name` AS `firstName` FROM `test`;', $select->build());

        // queries without table
        $select = $this->select()->columns(new RawExp('ISNULL(1+1)'));
        $this->assertSame('SELECT ISNULL(1+1);', $select->build());

        $select = $this->select()->columns(new RawExp('INTERVAL(23, 1, 15, 17, 30, 44, 200)'));
        $this->assertSame('SELECT INTERVAL(23, 1, 15, 17, 30, 44, 200);', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::where
     * @covers ::alias
     * @covers ::getAliasSql
     * @covers ::getColumnsSql
     * @covers \Odan\Database\Condition::addClauseCondClosure
     * @covers \Odan\Database\Condition::getRightFieldValue
     * @covers \Odan\Database\Condition::getWhereSql
     * @covers \Odan\Database\Condition::getConditionSql
     * @covers ::columns
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testSubselect()
    {
        // Raw
        $select = $this->select()
            ->columns('id', new RawExp('(SELECT MAX(payments.amount) FROM payments) AS max_amount'))
            ->from('test');
        $this->assertSame('SELECT `id`,(SELECT MAX(payments.amount) FROM payments) AS max_amount FROM `test`;', $select->build());

        // With a sub query object
        $select = $this->select()
            ->columns('id', function (SelectQuery $subSelect) {
                $subSelect->columns(new RawExp('MAX(payments.amount)'))
                    ->from('payments')
                    ->alias('max_amount'); // AS max_amount
            })
            ->from('test');

        $this->assertSame('SELECT `id`,(SELECT MAX(payments.amount) FROM `payments`) AS `max_amount` FROM `test`;', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::union
     * @covers ::unionAll
     * @covers ::unionDistinct
     * @covers ::getUnionSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testUnion()
    {
        $select = $this->select()->columns('id')->from('table1');
        $select2 = $this->select()->columns('id')->from('table2');
        $select->union($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION SELECT `id` FROM `table2`;', $select->build());

        $select = $this->select()->columns('id')->from('table1');
        $select2 = $this->select()->columns('id')->from('table2');
        $select->unionAll($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION ALL SELECT `id` FROM `table2`;', $select->build());

        $select = $this->select()->columns('id')->from('table1');
        $select2 = $this->select()->columns('id')->from('table2');
        $select->unionDistinct($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION DISTINCT SELECT `id` FROM `table2`;', $select->build());
    }

    /**
     * Test.
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
        $this->assertSame('SELECT `id` FROM `test`;', $select->build());

        $select = $this->select()->columns('id')->from('test AS t');
        $this->assertSame('SELECT `id` FROM `test` AS `t`;', $select->build());

        $select = $this->select()->columns('id')->from('dbname.test AS t');
        $this->assertSame('SELECT `id` FROM `dbname`.`test` AS `t`;', $select->build());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = '1';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '>=', 3);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` >= '3';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '>', 4);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` > '4';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<', 5);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` < '5';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<=', 6);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` <= '6';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<>', 7);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` <> '7';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '!=', 8);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` != '8';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '<>', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '!=', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', '=', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NULL;', $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'is', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NULL;', $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'is not', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns('*')->from('users')->where('username', '=', "hello' or 1=1;--");
        $this->assertSame("SELECT * FROM `users` WHERE `username` = 'hello\' or 1=1;--';", $select->build());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` IN ('1', 'a', '\'b', NULL);", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'not in', [2, 'a', "'b", null]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` NOT IN ('2', 'a', '\'b', NULL);", $select->build());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = GREATEST ('1', '2', '\'b', NULL);", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'interval', [1, 2, 3]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = INTERVAL ('1', '2', '3');", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('id', 'strcmp', ['text', "text'2"]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = STRCMP ('text', 'text\'2');", $select->build());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` BETWEEN '1' AND '100';", $select->build());
    }

    /**
     * Test Simple pattern matching.
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
        $select = $this->select()->columns('id')->from('test')->where('first_name', 'like', '%max%');
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` LIKE '%max%';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('first_name', 'like', "%a'b%");
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` LIKE '%a\'b%';", $select->build());

        $select = $this->select()->columns('id')->from('test')->where('first_name', 'not like', "%a'1%");
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` NOT LIKE '%a\'1%';", $select->build());
    }

    /**
     * Test Simple pattern matching.
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
        $this->assertSame("SELECT * FROM `users` WHERE `username` REGEXP '^[a-d]';", $select->build());

        $select = $this->select()->from('users')->where('username', Operator::REGEXP, 'new\\*.\\*line');
        $this->assertSame("SELECT * FROM `users` WHERE `username` REGEXP 'new\\\\*.\\\\*line';", $select->build());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT `id` FROM `test` WHERE STRCMP('text', 'text2');", $select->build());

        $select = $this->select()->columns('id')->from('test')->where(new RawExp('ISNULL(1+1)'));
        $this->assertSame('SELECT `id` FROM `test` WHERE ISNULL(1+1);', $select->build());
    }

    /**
     * Test.
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
        $this->assertSame('SELECT * FROM `users` WHERE `first_name` = `last_name`;', $select->build());

        $select = $select->orWhereColumn('votes', '>=', 'vote_max');
        $this->assertSame('SELECT * FROM `users` WHERE `first_name` = `last_name` OR `votes` >= `vote_max`;', $select->build());

        $select = $this->select()->from('users')->whereColumn('users.email', '=', 'table2.email');
        $this->assertSame('SELECT * FROM `users` WHERE `users`.`email` = `table2`.`email`;', $select->build());

        $select = $this->select()->from('users')
            ->whereColumn('first_name', '=', 'last_name')
            ->whereColumn('updated_at', '=', 'created_at');
        $this->assertSame('SELECT * FROM `users` WHERE `first_name` = `last_name` AND `updated_at` = `created_at`;', $select->build());
    }

    /**
     * Test.
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
            ->groupBy('id', 'username', new RawExp('`role`'))
            ->orderBy('id ASC', 'username DESC', new RawExp('`role` ASC'))
            ->limit(10)
            ->offset(5);

        $sql = $select->build();
        $expected = 'c2b7274a1f54189f25d87590b2250d55534c31f4';
        $actual = sha1($sql);
        if ($expected != $actual) {
            echo "\nSQL: $sql\n";
            file_put_contents(__DIR__ . '/debug.sql', $sql);
        }
        $this->assertSame($expected, $actual);
    }

    /**
     * Test.
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
            ->from('test AS t')
            ->join('users AS u', 'u.id', '=', 'test.user_id');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT `id` FROM `test` AS `t` INNER JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id`;', $select->build());

        $select->join('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = 'SELECT `id` FROM `test` AS `t` INNER JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id` INNER JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`;';
        $this->assertSame($expected, $select->build());
    }

    /**
     * Test.
     *
     * @covers ::joinRaw
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testJoinRaw()
    {
        $select = $this->select()
            ->columns('id')
            ->from('test')
            ->joinRaw('users u', 't2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT `id` FROM `test` INNER JOIN `users` `u` ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL);', $select->build());
    }

    /**
     * Test.
     *
     * @covers ::leftJoinRaw
     * @covers ::getJoinSql
     * @covers ::from
     * @covers ::prepare
     * @covers ::build
     */
    public function testLeftJoinRaw()
    {
        $select = $this->select()
            ->columns('id')
            ->from('test')
            ->leftJoinRaw('users u', 't2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT `id` FROM `test` LEFT JOIN `users` `u` ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL);', $select->build());
    }

    /**
     * Test.
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
        $this->assertSame('SELECT `id` FROM `test` LEFT JOIN `users` `u` ON `u`.`id` = `test`.`user_id`;', $select->build());

        $select->leftJoin('table2 AS t2', 't2.id', '=', 'test.user_id');
        $expected = 'SELECT `id` FROM `test` LEFT JOIN `users` `u` ON `u`.`id` = `test`.`user_id` LEFT JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`;';
        $this->assertSame($expected, $select->build());
    }

    /**
     * Test.
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
        $this->assertSame('SELECT `id` FROM `test` LIMIT 10;', $select->build());
    }

    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }
}
