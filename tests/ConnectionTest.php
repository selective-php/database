<?php

declare(strict_types = 1);

namespace Selective\Database\Test;

use PDO;
use Selective\Database\Connection;

/**
 * @coversDefaultClass \Selective\Database\Connection
 */
class ConnectionTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance(): void
    {
        $connection = $this->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);
    }

    /**
     * Test.
     */
    public function testPrepareQuery(): void
    {
        $select = $this->select();
        $select->columns(['TABLE_NAME'])
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', '=', 'TABLES');

        $statement = $select->prepare();

        $statement->execute();

        /** @var array $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row['TABLE_NAME']);
        $this->assertSame('TABLES', $row['TABLE_NAME']);
    }
}
