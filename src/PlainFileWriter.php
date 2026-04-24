<?php

namespace samdark\sitemap;

use RuntimeException;

/**
 * Writes the given data as-is into a file.
 */
class PlainFileWriter implements WriterInterface
{
    /**
     * @var ?resource For target file.
     */
    private $file;

    /**
     * @param string $filename Target file.
     */
    public function __construct(string $filename)
    {
        $file = fopen($filename, 'ab');
        if ($file === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Unable to open file \"$filename\".");
            // @codeCoverageIgnoreEnd
        }
        $this->file = $file;
    }

    public function append(string $data): void
    {
        if ($this->file === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        fwrite($this->file, $data);
    }

    public function finish(): void
    {
        if ($this->file === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        fclose($this->file);
        $this->file = null;
    }
}
