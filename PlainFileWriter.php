<?php

namespace samdark\sitemap;

/**
 * Writes the given data as-is into a file
 */
class PlainFileWriter implements WriterInterface
{
    /**
     * @var resource for target file
     */
    private $file;

    /**
     * @param string $filename target file
     */
    public function __construct($filename)
    {
        $this->file = fopen($filename, 'ab');
    }

    /**
     * @inheritdoc
     */
    public function append($data)
    {
        assert($this->file !== null);

        fwrite($this->file, $data);
    }

    /**
     * @inheritdoc
     */
    public function finish()
    {
        assert($this->file !== null);

        fclose($this->file);
        $this->file = null;
    }
}
