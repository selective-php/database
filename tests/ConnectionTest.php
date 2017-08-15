<?php

namespace Odan\Test;

use Odan\Database\Connection;

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
    }

    /**
     * Test ping method
     *
     * @return void
     * @covers ::ping
     */
    public function testPing()
    {
        $db = $this->getConnection();
        $result = $db->ping();
        $this->assertEquals(true, $result);
    }

    /**
     * Test esc method
     *
     * @return void
     * @covers ::esc
     */
    public function testEsc()
    {
        $db = $this->getConnection();
        $this->assertEquals('NULL', $db->esc(null));
        $this->assertEquals("'\\0'", $db->esc("\0"));
        $this->assertEquals("'0'", $db->esc(0));
        $this->assertEquals("'0'", $db->esc('0'));
        $this->assertEquals("''", $db->esc(false));
        $this->assertEquals("'1'", $db->esc(true));
        $this->assertEquals("'-1'", $db->esc(-1));
        $this->assertEquals("'abc123'", $db->esc("abc123"));
        $this->assertEquals("'öäüÖÄÜß'", $db->esc("öäüÖÄÜß"));
        $this->assertEquals("'?'", $db->esc('?'));
        $this->assertEquals("':'", $db->esc(':'));
        $this->assertEquals("'\\''", $db->esc("'"));
        $this->assertEquals("'\\\"'", $db->esc("\""));
        $this->assertEquals("'\\\\'", $db->esc("\\"));
        $this->assertEquals("'\\0'", $db->esc("\x00"));
        $this->assertEquals("'\\Z'", $db->esc("\x1a"));
        $this->assertEquals("'\\n'", $db->esc("\n"));
        $this->assertEquals("'\\r'", $db->esc("\r"));
        $this->assertEquals("','", $db->esc(","));
        $this->assertEquals("'\\','", $db->esc("',"));
        $this->assertEquals("'`'", $db->esc("`"));
        $this->assertEquals("'%s'", $db->esc("%s"));
        $this->assertEquals("'Naughty \\' string'", $db->esc("Naughty ' string"));
        $this->assertEquals("'@þÿ€'", $db->esc("@þÿ€"));
        // Injection patterns
        $this->assertEquals("'\\' OR \\'\\'=\\''", $db->esc("' OR ''='"));
        $this->assertEquals("'1\\' or \\'1\\' = \\'1'", $db->esc("1' or '1' = '1"));
        $this->assertEquals("'1\\' or \\'1\\' = \\'1\\'))/*'", $db->esc("1' or '1' = '1'))/*"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     */
    public function testIdent()
    {
        $db = $this->getConnection();

        $this->assertEquals("`0`", $db->ident(0));
        $this->assertEquals("`0`", $db->ident('0'));
        $this->assertEquals("`1`", $db->ident(true));
        $this->assertEquals("`-1`", $db->ident(-1));
        $this->assertEquals("`abc123`", $db->ident("abc123"));
        $this->assertEquals("`öäüÖÄÜß`", $db->ident("öäüÖÄÜß"));
        $this->assertEquals("`?`", $db->ident('?'));
        $this->assertEquals("`:`", $db->ident(':'));
        $this->assertEquals("`\\'`", $db->ident("'"));
        $this->assertEquals("`\\\"`", $db->ident("\""));
        $this->assertEquals("`\\\`", $db->ident("\\"));
        $this->assertEquals("`\\Z`", $db->ident("\x1a"));

        $this->assertEquals("`,`", $db->ident(","));
        $this->assertEquals("`\\',`", $db->ident("',"));
        $this->assertEquals("```", $db->ident("`"));
        $this->assertEquals("`%s`", $db->ident("%s"));
        $this->assertEquals("`Naughty \\' string`", $db->ident("Naughty ' string"));
        $this->assertEquals("`@þÿ€`", $db->ident("@þÿ€"));

        // With database name
        $this->assertSame("`dbname`.`tablename`", $db->ident("dbname.tablename"));
        $this->assertSame("`dbname`.`tablename`.`field`", $db->ident("dbname.tablename.field"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull()
    {
        $db = $this->getConnection();
        $db->ident(null);
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull2()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\0`", $db->ident("\x00"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull3()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\n`", $db->ident("\n"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull4()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\r`", $db->ident("\r"));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull5()
    {
        $db = $this->getConnection();
        $this->assertEquals("``", $db->ident(false));
    }

    /**
     * Test ident method
     *
     * @return void
     * @covers ::ident
     * @expectedException InvalidArgumentException
     */
    public function testIdentNull6()
    {
        $db = $this->getConnection();
        $this->assertEquals("`\\0`", $db->ident("\0"));
    }
}
