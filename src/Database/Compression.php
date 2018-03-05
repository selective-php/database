<?php

namespace Odan\Database;

/**
 * Class Compression
 */
class Compression
{

    /** @var Connection */
    protected $db = null;

    /**
     * Compression constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Compress compatible with MySQL COMPRESS
     *
     * @param string|mixed $value data to compress
     * @return string|null compressed data
     */
    public function compress($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return pack('L', strlen($value)) . gzcompress($value);
    }

    /**
     * Uncompress compatible with MySQL UNCOMPRESS
     *
     * @param string|mixed $value
     * @return string|null
     */
    public function uncompress($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return gzuncompress(substr($value, 4));
    }
}
