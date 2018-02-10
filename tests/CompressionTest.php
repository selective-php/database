<?php

namespace Odan\Test;

use Odan\Database\Compression;

/**
 * @coversDefaultClass \Odan\Database\Compression
 */
class CompressionTest extends BaseTest
{
    /**
     * @var Compression
     */
    protected $compression;

    /**
     * Return Data object
     *
     * @return Compression
     */
    public function getCompression()
    {
        if ($this->compression === null) {
            $this->compression = new Compression($this->getConnection());
        }
        return $this->compression;
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $object = $this->getCompression();
        $this->assertInstanceOf(Compression::class, $object);
    }

    /**
     * Test compress method
     *
     * @return void
     * @covers ::compress
     * @covers ::uncompress
     */
    public function testCompress()
    {
        $db = $this->getConnection();
        $enc = $this->getCompression();

        $result = $enc->compress('test');
        $result = strtoupper(bin2hex($result));
        $resultFromDb = $db->queryValue("SELECT HEX(COMPRESS('test')) AS result;", 'result');
        $this->assertSame($resultFromDb, $result);

        // MySQL TO_BASE64 function does not exist
        // https://github.com/travis-ci/travis-ci/issues/4088
        $result = $enc->compress(null);
        $result2 = $db->queryValue("SELECT HEX(COMPRESS(NULL)) AS result;", 'result');
        $this->assertSame(true, $result === $result2);

        $result = $enc->uncompress(hex2bin('04000000789C2B492D2E0100045D01C1'));
        $resultFromDb = $db->queryValue("SELECT UNCOMPRESS(UNHEX('04000000789C2B492D2E0100045D01C1')) AS result;", 'result');
        $this->assertSame($resultFromDb, $result);

        $result = $enc->uncompress(null);
        $resultFromDb = $db->queryValue("SELECT UNCOMPRESS(NULL) AS result;", 'result');
        $this->assertSame(true, $resultFromDb === $result);

        // MySQL TO_BASE64 function does not exist
        // https://github.com/travis-ci/travis-ci/issues/4088
    }
}
