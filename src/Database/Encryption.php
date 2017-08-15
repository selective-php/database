<?php

namespace Odan\Database;

class Encryption
{

    /** @var Connection */
    protected $db = null;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Compress compatible with MySQL COMPRESS
     *
     * @param string|mixed $value data to compress
     * @param bool $encodeBase64 encode to base64 (default = false)
     * @return string|null compressed data
     */
    public function compress($value, $encodeBase64 = false)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $result = pack('L', strlen($value)) . gzcompress($value);
        if ($encodeBase64 === true) {
            $result = base64_encode($result);
        }
        return $result;
    }

    /**
     * Uncompress compatible with MySQL UNCOMPRESS
     *
     * @param string|mixed $value
     * @param bool decodeBase64 decode from base64 (default = false)
     * @return string|null
     */
    public function uncompress($value, $decodeBase64 = false)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if ($decodeBase64 === true) {
            $value = base64_decode($value);
        }
        $value = gzuncompress(substr($value, 4));
        return $value;
    }
}
