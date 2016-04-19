<?php
namespace samdark\sitemap;

use XMLWriter;

/**
 * A class for generating Sitemap index (http://www.sitemaps.org/)
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Index
{
    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @var string index file path
     */
    private $filePath;

    /**
     * @var bool whether to gzip the resulting file or not
     */
    private $gzip = false;

    /**
     * @param string $filePath index file path
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Creates new file
     */
    private function createNewFile()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent(true);
        $this->writer->startElement('sitemapindex');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    }

    /**
     * Adds sitemap link to the index file
     *
     * @param string $location URL of the sitemap
     * @param integer $lastModified unix timestamp of sitemap modification time
     * @throws \InvalidArgumentException
     */
    public function addSitemap($location, $lastModified = null)
    {
        if (false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }

        if ($this->writer === null) {
            $this->createNewFile();
        }

        $this->writer->startElement('sitemap');
        $this->writer->writeElement('loc', $location);

        if ($lastModified !== null) {
            $this->writer->writeElement('lastmod', date('c', $lastModified));
        }
        $this->writer->endElement();
    }

    /**
     * @return string index file path
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Finishes writing
     */
    public function write()
    {
        if ($this->writer instanceof XMLWriter) {
            $this->writer->endElement();
            $this->writer->endDocument();
            $filePath = $this->getFilePath();
            if ($this->gzip) {
                $filePath = 'compress.zlib://'.$filePath;
            }
            file_put_contents($filePath, $this->writer->flush());
        }
    }

    /**
     * Sets whether the resulting file will be gzipped or not.
     * @param bool $bool
     */
    public function setGzip($bool)
    {
        $gzip = (bool)$bool;
        if ($bool && !extension_loaded('zlib')) {
            throw new \RuntimeException('Zlib extension must be installed to gzip the sitemap.');
        }
        $this->gzip = $bool;
    }
}
