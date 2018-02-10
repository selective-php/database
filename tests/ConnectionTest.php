<?php declare(strict_types=1);

namespace Odan\Test;

use Odan\Database\Connection;
use PDO;
use PDOStatement;

/**
 * @coversDefaultClass Odan\Database\Connection
 */
class ConnectionTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $connection = $this->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);
        $pdo = $this->pdoMethod($connection);
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * @param PDO $pdo
     * @return PDO
     */
    protected function pdoMethod(PDO $pdo)
    {
        return $pdo;
    }

    /**
     * Test
     *
     */
    public function testPrepareQuery()
    {
        $select = $this->select();
        $select->columns('TABLE_NAME')
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', '=', 'TABLES');

        $statement = $select->prepare();
        $this->assertInstanceOf(PDOStatement::class, $statement);

        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        $this->assertTrue(!empty($row['TABLE_NAME']));
        $this->assertSame('TABLES', $row['TABLE_NAME']);
    }
}
