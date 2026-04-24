<?php

namespace samdark\sitemap;

use DeflateContext;
use RuntimeException;

/**
 * Flushes buffer into file with incremental deflating data, available in PHP 7.0+
 */
class DeflateWriter implements WriterInterface
{
    /**
     * @var ?resource For target file.
     */
    private $file = null;

    /**
     * @var DeflateContext|null For writable incremental deflate context.
     */
    private $deflateContext = null;

    /**
     * @param string $filename Target file.
     */
    public function __construct(string $filename)
    {
        if (!function_exists('deflate_init')) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('ext-zlib is not available.');
            // @codeCoverageIgnoreEnd
        }

        $file = fopen($filename, 'ab');
        if ($file === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Unable to open \"$filename\".");
            // @codeCoverageIgnoreEnd
        }
        $this->file = $file;

        $deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
        if ($deflateContext === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Unable to open deflate context.");
            // @codeCoverageIgnoreEnd
        }
        $this->deflateContext = $deflateContext;
    }

    /**
     * Deflate data in a deflate context and write it to the target file.
     *
     * @param string $data Data to write.
     * @param int $flushMode zlib flush mode to use for writing.
     */
    private function write(string $data, int $flushMode): void
    {
        if ($this->file === null || $this->deflateContext === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $compressedChunk = deflate_add($this->deflateContext, $data, $flushMode);
        if ($compressedChunk === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to add deflate.');
            // @codeCoverageIgnoreEnd
        }
        fwrite($this->file, $compressedChunk);
    }

    /**
     * Store data in a deflate stream.
     *
     * @param string $data
     */
    public function append(string $data): void
    {
        $this->write($data, ZLIB_NO_FLUSH);
    }

    /**
     * Make sure all data was written.
     */
    public function finish(): void
    {
        $this->write('', ZLIB_FINISH);

        $this->file = null;
        $this->deflateContext = null;
    }
}
