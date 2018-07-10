<?php

namespace SamDark\Sitemap\Writer;

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
    public function append($data): void
    {
        \assert($this->file !== null);

        fwrite($this->file, $data);
    }

    /**
     * @inheritdoc
     */
    public function finish(): void
    {
        \assert($this->file !== null);

        fclose($this->file);
        $this->file = null;
    }
}
