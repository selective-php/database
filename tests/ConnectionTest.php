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
     * @return void
     * @covers ::quoteValue
     */
    public function testEsc()
    {
        $db = $this->getConnection();
        $this->assertEquals('NULL', $db->quoteValue(null));
        $this->assertEquals("'\\0'", $db->quoteValue("\0"));
        $this->assertEquals("'0'", $db->quoteValue(0));
        $this->assertEquals("'0'", $db->quoteValue('0'));
        $this->assertEquals("''", $db->quoteValue(false));
        $this->assertEquals("'1'", $db->quoteValue(true));
        $this->assertEquals("'-1'", $db->quoteValue(-1));
        $this->assertEquals("'abc123'", $db->quoteValue("abc123"));
        $this->assertEquals("'öäüÖÄÜß'", $db->quoteValue("öäüÖÄÜß"));
        $this->assertEquals("'?'", $db->quoteValue('?'));
        $this->assertEquals("':'", $db->quoteValue(':'));
        $this->assertEquals("'\\''", $db->quoteValue("'"));
        $this->assertEquals("'\\\"'", $db->quoteValue("\""));
        $this->assertEquals("'\\\\'", $db->quoteValue("\\"));
        $this->assertEquals("'\\0'", $db->quoteValue("\x00"));
        $this->assertEquals("'\\Z'", $db->quoteValue("\x1a"));
        $this->assertEquals("'\\n'", $db->quoteValue("\n"));
        $this->assertEquals("'\\r'", $db->quoteValue("\r"));
        $this->assertEquals("','", $db->quoteValue(","));
        $this->assertEquals("'\\','", $db->quoteValue("',"));
        $this->assertEquals("'`'", $db->quoteValue("`"));
        $this->assertEquals("'%s'", $db->quoteValue("%s"));
        $this->assertEquals("'Naughty \\' string'", $db->quoteValue("Naughty ' string"));
        $this->assertEquals("'@þÿ€'", $db->quoteValue("@þÿ€"));
        // Injection patterns
        $this->assertEquals("'\\' OR \\'\\'=\\''", $db->quoteValue("' OR ''='"));
        $this->assertEquals("'1\\' or \\'1\\' = \\'1'", $db->quoteValue("1' or '1' = '1"));
        $this->assertEquals("'1\\' or \\'1\\' = \\'1\\'))/*'", $db->quoteValue("1' or '1' = '1'))/*"));
    }

    /**
     * Test
     *
     * @return void
     * @covers ::quoteName
     * @covers ::quoteNameWithSeparator
     * @covers ::quoteIdentifier
     */
    public function testIdent()
    {
        $db = $this->getConnection();

        $this->assertSame("``", $db->quoteName(''));
        $this->assertSame("*", $db->quoteName('*'));

        // Table
        $this->assertSame("`abc123`", $db->quoteName("abc123"));
        $this->assertSame("`user_roles`", $db->quoteName("user_roles "));
        $this->assertSame("`öäüÖÄÜß`", $db->quoteName("öäüÖÄÜß"));
        $this->assertSame("`table`.*", $db->quoteName("table.*"));

        // Table with alias
        $this->assertSame("`users` `u`", $db->quoteName("users u"));
        $this->assertSame("`users` AS `u`", $db->quoteName("users AS u"));

        // With database name
        $this->assertSame("`dbname`.`tablename`", $db->quoteName("dbname.tablename"));
        $this->assertSame("`dbname`.`tablename`.`field`", $db->quoteName("dbname.tablename.field"));
        // Alias.field AS thing
        $this->assertSame("`dbname`.`tablename`.`field` AS `thing`", $db->quoteName("dbname.tablename.field AS thing"));

        $this->assertSame("`.`", $db->quoteName('.'));
        $this->assertSame("`?`", $db->quoteName('?'));
        $this->assertSame("`:`", $db->quoteName(':'));
        $this->assertSame("`,`", $db->quoteName(","));
        $this->assertSame("`',`", $db->quoteName("',"));
        $this->assertSame("````", $db->quoteName("`"));
        $this->assertSame("`%s`", $db->quoteName("%s"));
        $this->assertSame("`Naughty-'-string`", $db->quoteName("Naughty-'-string"));
        $this->assertSame("`@þÿ€`", $db->quoteName("@þÿ€"));
    }

    /**
     * Test
     *
     * @return void
     * @covers ::quoteArray
     */
    public function testQuoteArray()
    {
        $db = $this->getConnection();
        $this->assertEquals([], $db->quoteArray([]));

        $row = ['1', '2', '3', null];
        $this->assertEquals(["'1'", "'2'", "'3'", 'NULL'], $db->quoteArray($row));
    }

    /**
     * Test
     *
     */
    public function testPrepareQuery()
    {
        $select = $this->getTable()->select();
        $select->columns(['TABLE_NAME'])
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', '=', 'TABLES');

        $statement = $select->prepare();
        $this->assertInstanceOf(PDOStatement::class, $statement);

        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(!empty($row['TABLE_NAME']));
        $this->assertEquals('TABLES', $row['TABLE_NAME']);
    }
}
