<?php

namespace samdark\sitemap;

/**
 * Flushes buffer into temporary stream and compresses stream into a file on finish
 */
class TempFileGZIPWriter implements WriterInterface
{
    /**
     * @var string Name of target file
     */
    private $filename;

    /**
     * @var ressource for php://temp stream
     */
    private $tempFile;

    /**
     * @param string $filename target file
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
        $this->tempFile = fopen('php://temp/', 'wb');
    }

    /**
     * Store data in a temporary stream/file
     *
     * @param string $data
     */
    public function append($data)
    {
        assert($this->tempFile !== null);

        fwrite($this->tempFile, $data);
    }

    /**
     * Deflate buffered data
     */
    public function finish()
    {
        assert($this->tempFile !== null);

        $file = fopen('compress.zlib://' . $this->filename, 'wb');
        rewind($this->tempFile);
        stream_copy_to_stream($this->tempFile, $file);

        fclose($file);
        fclose($this->tempFile);
        $this->tempFile = null;
    }
}
