<?php

namespace Selective\Database\Test;

use PDOStatement;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

/**
 * @coversDefaultClass \Selective\Database\SelectQuery
 */
class SelectQueryTest extends BaseTest
{
    /**
     * Set Up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * Test create object.
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(SelectQuery::class, $this->select());
    }

    /**
     * Test.
     */
    public function testDistinct(): void
    {
        $select = $this->select()->distinct()->columns(['id'])->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT DISTINCT `id` FROM `test`;', $select->build());

        $select = $this->select()->distinctRow()->columns(['id'])->from('test');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT DISTINCTROW `id` FROM `test`;', $select->build());
    }

    /**
     * Test.
     */
    public function testStraightJoin(): void
    {
        $select = $this->select()->straightJoin()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT STRAIGHT_JOIN * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testHighPriority(): void
    {
        $select = $this->select()->highPriority()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT HIGH_PRIORITY * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testSmallResult(): void
    {
        $select = $this->select()->smallResult()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_SMALL_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testBigResult(): void
    {
        $select = $this->select()->bigResult()->from('users');
        $this->assertInstanceOf(PDOStatement::class, $select->prepare());
        $this->assertSame('SELECT SQL_BIG_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testBufferResult(): void
    {
        $select = $this->select()->bufferResult()->from('users');
        $this->assertSame('SELECT SQL_BUFFER_RESULT * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testCalcFoundRows(): void
    {
        $select = $this->select()->calcFoundRows()->from('users');
        $this->assertSame('SELECT SQL_CALC_FOUND_ROWS * FROM `users`;', $select->build());
    }

    /**
     * Test.
     */
    public function testColumns(): void
    {
        $select = $this->select()->from('users');
        $this->assertSame('SELECT * FROM `users`;', $select->build());

        $select = $this->select()->columns(['id', 'username', ['firstName' => 'first_name']])->from('test');
        $this->assertSame('SELECT `id`,`username`,`first_name` AS `firstName` FROM `test`;', $select->build());

        $select = $this->select()->columns(['id', 'username', ['firstName' => 'first_name']])->from('test');
        $this->assertSame('SELECT `id`,`username`,`first_name` AS `firstName` FROM `test`;', $select->build());

        // queries without table
        $select = $this->select()->columns([new RawExp('ISNULL(1+1)')]);
        $this->assertSame('SELECT ISNULL(1+1);', $select->build());

        $select = $this->select()->columns([new RawExp('INTERVAL(23, 1, 15, 17, 30, 44, 200)')]);
        $this->assertSame('SELECT INTERVAL(23, 1, 15, 17, 30, 44, 200);', $select->build());
    }

    /**
     * Test.
     */
    public function testColumnsArray(): void
    {
        $query = $this->select()->from('test');

        $query->columns([
            'id',
            'username',
            'firstName' => 'first_name',
            'last_name' => 'test.last_name',
            'email' => 'database.test.email',
            'value' => $query->raw('CONCAT("1","2")'),
        ]);

        $this->assertSame('SELECT `id`,`username`,`first_name` AS `firstName`,`test`.`last_name` AS `last_name`,' .
            '`database`.`test`.`email` AS `email`,CONCAT("1","2") AS `value` FROM `test`;', $query->build());
    }

    /**
     * Test.
     */
    public function testMultipleColumns(): void
    {
        $select = $this->select()->columns(['id', 'username', ['firstName' => 'first_name']])->from('test');
        $select = $select->columns([['username2' => 'username'], 'id2', 'table.fieldname2']);

        $sql = $select->build();
        $this->assertSame(
            'SELECT `id`,`username`,`first_name` AS `firstName`,`username` AS ' .
            '`username2`,`id2`,`table`.`fieldname2` FROM `test`;',
            $sql
        );
    }

    /**
     * Test.
     */
    public function testSubselect(): void
    {
        // Raw
        $select = $this->select()
            ->columns(['id', new RawExp('(SELECT MAX(payments.amount) FROM payments) AS max_amount')])
            ->from('test');
        $this->assertSame(
            'SELECT `id`,(SELECT MAX(payments.amount) FROM payments) AS max_amount FROM `test`;',
            $select->build()
        );

        // With a sub query object
        $select = $this->select()
            ->columns([
                'id',
                function (SelectQuery $subSelect) {
                    $subSelect->columns([new RawExp('MAX(payments.amount)')])
                        ->from('payments')
                        ->alias('max_amount'); // AS max_amount
                },
            ])
            ->from('test');

        $this->assertSame(
            'SELECT `id`,(SELECT MAX(payments.amount) FROM `payments`) AS `max_amount` FROM `test`;',
            $select->build()
        );
    }

    /**
     * Test.
     */
    public function testUnion(): void
    {
        $select = $this->select()->columns(['id'])->from('table1');
        $select2 = $this->select()->columns(['id'])->from('table2');
        $select->union($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION SELECT `id` FROM `table2`;', $select->build());

        $select = $this->select()->columns(['id'])->from('table1');
        $select2 = $this->select()->columns(['id'])->from('table2');
        $select->unionAll($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION ALL SELECT `id` FROM `table2`;', $select->build());

        $select = $this->select()->columns(['id'])->from('table1');
        $select2 = $this->select()->columns(['id'])->from('table2');
        $select->unionDistinct($select2);
        $this->assertSame('SELECT `id` FROM `table1` UNION DISTINCT SELECT `id` FROM `table2`;', $select->build());
    }

    /**
     * Test.
     */
    public function testFrom(): void
    {
        $select = $this->select()->columns(['id'])->from('test');
        $this->assertSame('SELECT `id` FROM `test`;', $select->build());

        $select = $this->select()->columns(['id'])->from(['t' => 'test']);
        $this->assertSame('SELECT `id` FROM `test` AS `t`;', $select->build());

        $select = $this->select()->columns(['id'])->from(['t' => 'dbname.test']);
        $this->assertSame('SELECT `id` FROM `dbname`.`test` AS `t`;', $select->build());
    }

    /**
     * Test.
     */
    public function testWhere(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', '=', 1);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = '1';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '>=', 3);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` >= '3';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '>', 4);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` > '4';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<', 5);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` < '5';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<=', 6);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` <= '6';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<>', 7);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` <> '7';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '!=', 8);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` != '8';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '<>', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '!=', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', '=', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NULL;', $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'is', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NULL;', $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'is not', null);
        $this->assertSame('SELECT `id` FROM `test` WHERE `id` IS NOT NULL;', $select->build());

        $select = $this->select()->columns(['*'])->from('users')->where('username', '=', "hello' or 1=1;--");
        $this->assertSame("SELECT * FROM `users` WHERE `username` = 'hello\' or 1=1;--';", $select->build());
    }

    /**
     * Test.
     */
    public function testWhereIn(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'in', [1, 'a', "'b", null]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` IN ('1', 'a', '\'b', NULL);", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'not in', [2, 'a', "'b", null]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` NOT IN ('2', 'a', '\'b', NULL);", $select->build());
    }

    /**
     * Test.
     */
    public function testWhereFunction(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'greatest', [1, '2', "'b", null]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = GREATEST ('1', '2', '\'b', NULL);", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'interval', [1, 2, 3]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = INTERVAL ('1', '2', '3');", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('id', 'strcmp', ['text', "text'2"]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` = STRCMP ('text', 'text\'2');", $select->build());
    }

    /**
     * Test.
     */
    public function testWhereBetween(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where('id', 'between', [1, 100]);
        $this->assertSame("SELECT `id` FROM `test` WHERE `id` BETWEEN '1' AND '100';", $select->build());
    }

    /**
     * Test Simple pattern matching.
     */
    public function testWhereLike(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'like', '%max%');
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` LIKE '%max%';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'like', "%a'b%");
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` LIKE '%a\'b%';", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where('first_name', 'not like', "%a'1%");
        $this->assertSame("SELECT `id` FROM `test` WHERE `first_name` NOT LIKE '%a\'1%';", $select->build());
    }

    /**
     * Test Simple pattern matching.
     */
    public function testWhereRegexp(): void
    {
        $select = $this->select()->from('users')->where('username', Operator::REGEXP, '^[a-d]');
        $this->assertSame("SELECT * FROM `users` WHERE `username` REGEXP '^[a-d]';", $select->build());

        $select = $this->select()->from('users')->where('username', Operator::REGEXP, 'new\\*.\\*line');
        $this->assertSame("SELECT * FROM `users` WHERE `username` REGEXP 'new\\\\*.\\\\*line';", $select->build());
    }

    /**
     * Test.
     */
    public function testWhereRawExp(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->where(new RawExp("STRCMP('text', 'text2')"));
        $this->assertSame("SELECT `id` FROM `test` WHERE STRCMP('text', 'text2');", $select->build());

        $select = $this->select()->columns(['id'])->from('test')->where(new RawExp('ISNULL(1+1)'));
        $this->assertSame('SELECT `id` FROM `test` WHERE ISNULL(1+1);', $select->build());
    }

    /**
     * Test.
     */
    public function testWhereRaw(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->whereRaw("STRCMP('text', 'text2')");
        $this->assertSame("SELECT `id` FROM `test` WHERE STRCMP('text', 'text2');", $select->build());
    }

    /**
     * Test.
     */
    public function testOrWhereRaw(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->whereRaw("STRCMP('text', 'text2')")
            ->orWhereRaw('1=1');
        $this->assertSame("SELECT `id` FROM `test` WHERE STRCMP('text', 'text2') OR 1=1;", $select->build());
    }

    /**
     * Test.
     */
    public function testOrWhereRawClosure(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->where(function (SelectQuery $query) {
                $query->where('field2', '=', 'value2');
            });

        $sql = $select->build();
        $this->assertSame("SELECT `id` FROM `test` WHERE (  `field2` = 'value2' );", $sql);
    }

    /**
     * Test.
     */
    public function testOrWhereRawClosure2(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->where(function (SelectQuery $query) {
                $query->where('field2', '=', 'value2')
                    ->whereRaw('0=0')
                    ->orWhereRaw('1=1');
            });

        $sql = $select->build();
        $this->assertSame("SELECT `id` FROM `test` WHERE (  `field2` = 'value2' AND 0=0 OR 1=1 );", $sql);
    }

    /**
     * Test.
     */
    public function testOrWhereRawClosure3(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->where('field', '=', 'value')
            ->where(function (SelectQuery $query) {
                $query->whereRaw('0=0')
                    ->orWhereRaw('1=1');
            });

        $sql = $select->build();
        $this->assertSame("SELECT `id` FROM `test` WHERE `field` = 'value' AND (  0=0 OR 1=1 );", $sql);
    }

    /**
     * Test.
     */
    public function testWhereColumn(): void
    {
        $select = $this->select()->from('users')->whereColumn('first_name', '=', 'last_name');
        $this->assertSame('SELECT * FROM `users` WHERE `first_name` = `last_name`;', $select->build());

        $select = $select->orWhereColumn('votes', '>=', 'vote_max');
        $this->assertSame(
            'SELECT * FROM `users` WHERE `first_name` = `last_name` OR `votes` >= `vote_max`;',
            $select->build()
        );

        $select = $this->select()->from('users')->whereColumn('users.email', '=', 'table2.email');
        $this->assertSame('SELECT * FROM `users` WHERE `users`.`email` = `table2`.`email`;', $select->build());

        $select = $this->select()->from('users')
            ->whereColumn('first_name', '=', 'last_name')
            ->whereColumn('updated_at', '=', 'created_at');
        $this->assertSame(
            'SELECT * FROM `users` WHERE `first_name` = `last_name` AND `updated_at` = `created_at`;',
            $select->build()
        );
    }

    /**
     * Test.
     */
    public function testWhereClosure(): void
    {
        $select = $this->select()
            ->distinct()
            ->columns(['id', 'username'])
            ->from(['u' => 'users'])
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

        $expected = 'd1de1a753a1a513e4e5b203ebfa7b8f75d7f2eec';
        $actual = sha1($sql);

        if ($expected !== $actual) {
            echo "\nSQL: $sql\n";
            file_put_contents(__DIR__ . '/debug.sql', $sql);
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * Test.
     */
    public function testHavingRaw(): void
    {
        $query = $this->select();
        $query->columns(['state_id', $query->func()->count('*')]);
        $query->from('table');
        $query->groupBy('state_id', 'locality');
        $query->havingRaw('COUNT(*) > 1');

        $this->assertSame(
            'SELECT `state_id`,COUNT(*) FROM `table` GROUP BY `state_id`, `locality` HAVING COUNT(*) > 1;',
            $query->build()
        );
    }

    /**
     * Test.
     */
    public function testOrHavingRaw(): void
    {
        $query = $this->select();
        $query->columns(['state_id', $query->func()->count('*')]);
        $query->from('table');
        $query->groupBy('state_id', 'locality');
        $query->havingRaw('COUNT(*) > 1');
        $query->orHavingRaw('brand LIKE %acme%');

        $this->assertSame(
            'SELECT `state_id`,COUNT(*) FROM `table` GROUP BY `state_id`, `locality` ' .
            'HAVING COUNT(*) > 1 OR brand LIKE %acme%;',
            $query->build()
        );
    }

    /**
     * Test.
     */
    public function testJoin(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from(['t' => 'test'])
            ->join(['u' => 'users'], 'u.id', '=', 'test.user_id');

        $this->assertSame(
            'SELECT `id` FROM `test` AS `t` INNER JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id`;',
            $select->build()
        );

        $select->innerJoin(['t2' => 'table2'], 't2.id', '=', 'test.user_id');
        $expected = 'SELECT `id` FROM `test` AS `t` INNER JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id` ' .
            'INNER JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`;';
        $this->assertSame($expected, $select->build());
    }

    /**
     * Test.
     */
    public function testJoinRaw(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->joinRaw(['u' => 'users'], 't2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL');

        $this->assertSame(
            'SELECT `id` FROM `test` INNER JOIN `users` AS `u` ON (t2.a=t1.a AND t3.b=t1.b ' .
            'AND t4.c=t1.c OR t2.b IS NULL);',
            $select->build()
        );
    }

    /**
     * Test.
     */
    public function testLeftJoinRaw(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->leftJoinRaw(['u' => 'users'], 't2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL');

        $this->assertSame(
            'SELECT `id` FROM `test` LEFT JOIN `users` AS `u` ' .
            'ON (t2.a=t1.a AND t3.b=t1.b AND t4.c=t1.c OR t2.b IS NULL);',
            $select->build()
        );
    }

    /**
     * Test.
     */
    public function testLeftJoin(): void
    {
        $select = $this->select()
            ->columns(['id'])
            ->from('test')
            ->leftJoin(['u' => 'users'], 'u.id', '=', 'test.user_id');

        $this->assertSame(
            'SELECT `id` FROM `test` LEFT JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id`;',
            $select->build()
        );

        $select->leftJoin(['t2' => 'table2'], 't2.id', '=', 'test.user_id');
        $expected = 'SELECT `id` FROM `test` ' .
            'LEFT JOIN `users` AS `u` ON `u`.`id` = `test`.`user_id` ' .
            'LEFT JOIN `table2` AS `t2` ON `t2`.`id` = `test`.`user_id`;';
        $this->assertSame($expected, $select->build());
    }

    /**
     * Test.
     */
    public function testLimit(): void
    {
        $select = $this->select()->columns(['id'])->from('test')->limit(10);
        $this->assertSame('SELECT `id` FROM `test` LIMIT 10;', $select->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRaw(): void
    {
        $query = $this->getConnection()->select();

        $this->assertEquals('value', $query->raw('value')->getValue());

        $query = $this->getConnection()->select();
        $query->columns([$query->raw('count(*) AS user_count'), 'status']);
        $query->from('payments');

        $this->assertEquals('SELECT count(*) AS user_count,`status` FROM `payments`;', $query->build());
    }
}
