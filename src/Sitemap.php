<?php
namespace samdark\sitemap;

use XMLWriter;

/**
 * A class for generating Sitemaps (http://www.sitemaps.org/)
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Sitemap
{
    /**
     * @var integer Maximum allowed number of bytes per single file.
     */
    private $maxFileSize = 10485760;

    /**
     * @var integer Current file size written
     */
    private $fileSize = 0;

    /**
     * @var integer Maximum allowed number of URLs in a single file.
     */
    private $maxUrls = 50000;

    /**
     * @var integer number of URLs added
     */
    private $urlsCount = 0;

    /**
     * @var string path to the file to be written
     */
    private $filePath;

    /**
     * @var integer number of files written
     */
    private $fileCount = 0;

    /**
     * @var array path of files written
     */
    private $writtenFilePaths = [];

    /**
     * @var integer number of URLs to be kept in memory before writing it to file
     */
    private $bufferSize = 1000;


    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @param string $filePath path of the file to write to
     * @throws \InvalidArgumentException
     */
    public function __construct($filePath)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }

        $this->filePath = $filePath;
    }

    /**
     * Creats new file
     */
    private function createNewFile()
    {
        $this->fileSize = 0;
        $this->fileCount++;
        $filePath = $this->getCurrentFilePath();
        $this->writtenFilePaths[] = $filePath;
        @unlink($filePath);

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent(true);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    }

    /**
     * Writes closing tags to current file
     */
    private function finishFile()
    {
        if ($this->writer !== null) {
            $this->writer->endElement();
            $this->writer->endDocument();
            $this->flush();
        }
    }

    /**
     * Finishes writing
     */
    public function write()
    {
        $this->finishFile();
    }

    /**
     * Flushes buffer into file
     */
    private function flush()
    {
        $buffer = $this->writer->flush(true);
        $this->fileSize += mb_strlen($buffer, '8bit');
        file_put_contents($this->getCurrentFilePath(), $buffer, FILE_APPEND);
    }

    private function getStringFromUrl(Url $url)
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->startElement('url');

        $writer->writeElement('loc', $url->getLocation());

        if ($url->getLastModified() !== null) {
            $writer->writeElement('lastmod', date('c', $url->getLastModified()));
        }

        if ($url->getChangeFrequency() !== null) {
            $writer->writeElement('changefreq', $url->getChangeFrequency());
        }

        if ($url->getPriority() !== null) {
            $writer->writeElement('priority', $url->getPriority());
        }

        $writer->endElement();

        return $writer->flush();
    }

    /**
     * Adds an URL to sitemap
     * @param Url $url
     */
    public function addItem(Url $url)
    {
        $urlString = $this->getStringFromUrl($url);

        if ($this->urlsCount === 0) {
            $this->createNewFile();
        } elseif ($this->urlsCount % $this->maxUrls === 0 || !$this->canWriteString($urlString)) {
            $this->finishFile();
            $this->createNewFile();
        }

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }

        $this->writer->writeRaw($urlString);

        $this->urlsCount++;
    }

    /**
     * @return string path of currently opened file
     */
    private function getCurrentFilePath()
    {
        if ($this->fileCount < 2) {
            return $this->filePath;
        }

        $parts = pathinfo($this->filePath);
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '_' . $this->fileCount . '.' . $parts['extension'];
    }

    /**
     * Returns an array of URLs written
     *
     * @param string $baseUrl base URL of all the sitemaps written
     * @return array URLs of sitemaps written
     */
    public function getSitemapUrls($baseUrl)
    {
        $urls = [];
        foreach ($this->writtenFilePaths as $file) {
            $urls[] = $baseUrl . pathinfo($file, PATHINFO_BASENAME);
        }
        return $urls;
    }

    /**
     * Sets maximum number of URLs to write in a single file.
     * Default is 50000.
     * @param integer $number
     */
    public function setMaxUrls($number)
    {
        $this->maxUrls = (int)$number;
    }

    /**
     * Sets number of URLs to be kept in memory before writing it to file.
     * Default is 1000.
     *
     * @param integer $number
     */
    public function setBufferSize($number)
    {
        $this->bufferSize = (int)$number;
    }

    /**
     * Sets maximum allowed number of bytes per single file.
     * Default is 10485760 bytes which equals to 10 megabytes.
     *
     * @param integer $bytes
     */
    public function setMaxFileSize($bytes)
    {
        $this->maxFileSize = (int)$bytes;
    }

    private function canWriteString($string)
    {
        $extraForClosingTags = 20;
        return $this->fileSize + mb_strlen($string, '8bit') + $extraForClosingTags < $this->maxFileSize;
    }
}
