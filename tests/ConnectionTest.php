<?php declare(strict_types=1);

namespace Odan\Test;

use Odan\Database\Connection;
use PDO;

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
     * Test esc method
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
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     */
    public function testIdent()
    {
        $db = $this->getConnection();

        $this->assertEquals("`0`", $db->quoteName(0));
        $this->assertEquals("`0`", $db->quoteName('0'));
        $this->assertEquals("`1`", $db->quoteName(true));
        $this->assertEquals("`-1`", $db->quoteName(-1));
        $this->assertEquals("`abc123`", $db->quoteName("abc123"));
        $this->assertEquals("`öäüÖÄÜß`", $db->quoteName("öäüÖÄÜß"));
        $this->assertEquals("`?`", $db->quoteName('?'));
        $this->assertEquals("`:`", $db->quoteName(':'));
        $this->assertEquals("`\\'`", $db->quoteName("'"));
        $this->assertEquals("`\\\"`", $db->quoteName("\""));
        $this->assertEquals("`\\\`", $db->quoteName("\\"));
        $this->assertEquals("`\\Z`", $db->quoteName("\x1a"));

        $this->assertEquals("`,`", $db->quoteName(","));
        $this->assertEquals("`\\',`", $db->quoteName("',"));
        $this->assertEquals("```", $db->quoteName("`"));
        $this->assertEquals("`%s`", $db->quoteName("%s"));
        $this->assertEquals("`Naughty \\' string`", $db->quoteName("Naughty ' string"));
        $this->assertEquals("`@þÿ€`", $db->quoteName("@þÿ€"));

        // With database name
        $this->assertSame("`dbname`.`tablename`", $db->quoteName("dbname.tablename"));
        $this->assertSame("`dbname`.`tablename`.`field`", $db->quoteName("dbname.tablename.field"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull()
    {
        $db = $this->getConnection();
        $db->quoteName(null);
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull2()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\0`", $db->quoteName("\x00"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull3()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\n`", $db->quoteName("\n"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull4()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\r`", $db->quoteName("\r"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull5()
    {
        $db = $this->getConnection();
        $this->assertEquals("``", $db->quoteName(false));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::quoteName
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull6()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\0`", $db->quoteName("\0"));
    }

    /**
     * Test
     *
     * @covers ::prepareQuery
     */
    public function testPrepareQuery()
    {
        //$db = $this->getConnection();
        $select = $this->getTable()->select();
        $select->columns(['TABLE_NAME'])
            ->from('information_schema.TABLES')
            ->where('TABLE_NAME', '=', 'TABLES');

        $statement = $select->getStatement();
        $this->assertInstanceOf(\PDOStatement::class, $statement);

        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(!empty($row['TABLE_NAME']));
        $this->assertEquals('TABLES', $row['TABLE_NAME']);
    }
}
