<?php

namespace Selective\Database\Test;

use Selective\Database\RawExp;
use Selective\Database\UpdateQuery;

/**
 * @coversDefaultClass \Selective\Database\UpdateQuery
 */
class UpdateQueryTest extends BaseTest
{
    /**
     * Set up.
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
     *
     * @return void
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->update());
    }

    /**
     * Create query.
     *
     * @return UpdateQuery The query
     */
    protected function update(): UpdateQuery
    {
        return new UpdateQuery($this->getConnection());
    }

    /**
     * Test.
     */
    public function testFrom(): void
    {
        $update = $this->update()->table('test')->set(['keyname' => 'admin'])->where('id', '=', '1');
        $this->assertSame("UPDATE `test` SET `keyname`='admin' WHERE `id` = '1';", $update->build());
        $this->assertTrue($update->execute());
    }

    /**
     * Test.
     */
    public function testLowPriority(): void
    {
        $update = $this->update()->lowPriority()->table('test')->set(['username' => 'admin']);
        $this->assertSame("UPDATE LOW_PRIORITY `test` SET `username`='admin';", $update->build());
    }

    /**
     * Test.
     */
    public function testIgnore(): void
    {
        $update = $this->update()->ignore()->table('test')->set(['username' => 'admin']);
        $this->assertSame("UPDATE IGNORE `test` SET `username`='admin';", $update->build());

        $update = $this->update()->lowPriority()->ignore()->table('test')->set(['username' => 'admin']);
        $this->assertSame("UPDATE LOW_PRIORITY IGNORE `test` SET `username`='admin';", $update->build());
    }

    /**
     * Test.
     */
    public function testOrderBy(): void
    {
        $update = $this->update()->table('users')->set(['username' => 'admin'])->orderBy('id');
        $this->assertSame("UPDATE `users` SET `username`='admin' ORDER BY `id`;", $update->build());

        $update = $this->update()->table('users')->set(['username' => 'admin'])->orderBy('id DESC');
        $this->assertSame("UPDATE `users` SET `username`='admin' ORDER BY `id` DESC;", $update->build());

        $update = $this->update()->table('users')->set(['username' => 'admin'])->orderBy('users.id ASC');
        $this->assertSame("UPDATE `users` SET `username`='admin' ORDER BY `users`.`id` ASC;", $update->build());

        $update = $this->update()->table('users')->set(['username' => 'admin'])->orderBy('db.users.id ASC');
        $this->assertSame("UPDATE `users` SET `username`='admin' ORDER BY `db`.`users`.`id` ASC;", $update->build());
    }

    /**
     * Test.
     */
    public function testLimit(): void
    {
        $update = $this->update()->table('test')->set(['username' => 'admin'])->limit(10);
        $this->assertSame("UPDATE `test` SET `username`='admin' LIMIT 10;", $update->build());
    }

    /**
     * Test.
     */
    public function testWhere(): void
    {
        $update = $this->update()->table('test')->set(['username' => 'admin'])
            ->where('test.id', '=', 1)
            ->orWhere('db.test.id', '>', 2);
        $this->assertSame(
            "UPDATE `test` SET `username`='admin' WHERE `test`.`id` = '1' OR `db`.`test`.`id` > '2';",
            $update->build()
        );
    }

    /**
     * Test.
     */
    public function testIncrementDecrement(): void
    {
        $update = $this->update()->table('users')->increment('voted');
        $this->assertSame("UPDATE `users` SET `voted`=`voted`+'1';", $update->build());

        $update = $this->update()->table('users')->increment('voted', 1)
            ->where('test.id', '=', 1)
            ->orWhere('db.test.id', '>', 2);
        $this->assertSame(
            "UPDATE `users` SET `voted`=`voted`+'1' WHERE `test`.`id` = '1' OR `db`.`test`.`id` > '2';",
            $update->build()
        );

        $update = $this->update()->table('users')->decrement('voted', 10)
            ->where('test.id', '=', 1)
            ->orWhere('db.test.id', '>', 2);
        $this->assertSame(
            "UPDATE `users` SET `voted`=`voted`-'10' WHERE `test`.`id` = '1' OR `db`.`test`.`id` > '2';",
            $update->build()
        );

        $update = $this->update()->table('users')->set(['votes' => new RawExp('votes+1')])->where('id', '=', '1');
        $this->assertSame("UPDATE `users` SET `votes`=votes+1 WHERE `id` = '1';", $update->build());
    }
}
