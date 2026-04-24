<?php

namespace samdark\sitemap;

use RuntimeException;

/**
 * Flushes buffer into temporary stream and compresses stream into a file on finish.
 *
 * Used on PHP builds where the zlib extension is available but incremental deflate functions are not.
 */
class TempFileGZIPWriter implements WriterInterface
{
    /**
     * @var string Name of target file.
     */
    private $filename;

    /**
     * @var ?resource For php://temp stream.
     */
    private $tempFile;

    /**
     * @param string $filename Target file.
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $tempFile = fopen('php://temp/', 'wb');
        if ($tempFile === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Unable to open temp file.');
            // @codeCoverageIgnoreEnd
        }
        $this->tempFile = $tempFile;
    }

    /**
     * Store data in a temporary stream/file.
     *
     * @param string $data Data to write.
     */
    public function append(string $data): void
    {
        if ($this->tempFile !== null) {
            fwrite($this->tempFile, $data);
        }
    }

    /**
     * Deflate buffered data.
     */
    public function finish(): void
    {
        if ($this->tempFile === null) {
            return;
        }

        if (is_dir($this->filename)) {
            throw new RuntimeException("Unable to open compress.zlib stream for \"$this->filename\".");
        }

        $file = fopen('compress.zlib://' . $this->filename, 'wb');
        if ($file === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Unable to open compress.zlib stream for \"$this->filename\".");
            // @codeCoverageIgnoreEnd
        }

        rewind($this->tempFile);
        stream_copy_to_stream($this->tempFile, $file);

        fclose($file);
        fclose($this->tempFile);
        $this->tempFile = null;
    }
}
