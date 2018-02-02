<?php

namespace samdark\sitemap;

/**
 * Flushes buffer into file with incremental deflating data, available in PHP 7.0+
 */
class DeflateWriter implements WriterInterface
{
    /**
     * @var resource for target file
     */
    private $file;

    /**
     * @var resource for writable incremental deflate context
     */
    private $deflateContext;

    /**
     * @param string $filename target file
     */
    public function __construct($filename)
    {
        $this->file = fopen($filename, 'ab');
        $this->deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
    }

    /**
     * Deflate data in a deflate context and write it to the target file
     *
     * @param string $data
     * @param int $flushMode zlib flush mode to use for writing
     */
    private function write($data, $flushMode)
    {
        assert($this->file !== null);

        $compressedChunk = deflate_add($this->deflateContext, $data, $flushMode);
        fwrite($this->file, $compressedChunk);
    }

    /**
     * Store data in a deflate stream
     *
     * @param string $data
     */
    public function append($data)
    {
        $this->write($data, ZLIB_NO_FLUSH);
    }

    /**
     * Make sure all data was written
     */
    public function finish()
    {
        $this->write('', ZLIB_FINISH);

        $this->file = null;
        $this->deflateContext = null;
    }
}
