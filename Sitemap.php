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
    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

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
     * @var array valid values for frequency parameter
     */
    private $validFrequencies = [
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER
    ];


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
        file_put_contents($this->getCurrentFilePath(), $this->writer->flush(true), FILE_APPEND);
    }

    /**
     * Adds a new item to sitemap
     *
     * @param string $location location item URL
     * @param integer $lastModified last modification timestamp
     * @param float $changeFrequency change frquency. Use one of self:: contants here
     * @param string $priority item's priority (0.0-1.0). Default null is equal to 0.5
     *
     * @throws \InvalidArgumentException
     */
    public function addItem($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        if ($this->urlsCount === 0) {
            $this->createNewFile();
        } elseif ($this->urlsCount % $this->maxUrls === 0) {
            $this->finishFile();
            $this->createNewFile();
        }

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }

        $this->writer->startElement('url');

        if (false === filter_var($location, FILTER_VALIDATE_URL)){
            throw new \InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }

        $this->writer->writeElement('loc', $location);

        if ($lastModified !== null) {
            $this->writer->writeElement('lastmod', date('c', $lastModified));
        }

        if ($changeFrequency !== null) {
            if (!in_array($changeFrequency, $this->validFrequencies, true)) {
                throw new \InvalidArgumentException(
                    'Please specify valid changeFrequency. Valid values are: '
                    . implode(', ', $this->validFrequencies)
                    . "You have specified: {$changeFrequency}."
                );
            }

            $this->writer->writeElement('changefreq', $changeFrequency);
        }

        if ($priority !== null) {
            if (!is_numeric($priority) || $priority < 0 || $priority > 1) {
                throw new \InvalidArgumentException(
                    "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: {$priority}."
                );
            }
            $this->writer->writeElement('priority', $priority);
        }

        $this->writer->endElement();

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
}
