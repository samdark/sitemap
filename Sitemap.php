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
    private $writtenFilePaths = array();

    /**
     * @var integer number of URLs to be kept in memory before writing it to file
     */
    private $bufferSize = 1000;

    /**
     * @var bool if XML should be indented
     */
    private $useIndent = true;

    /**
     * @var array valid values for frequency parameter
     */
    private $validFrequencies = array(
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER
    );

    /**
     * @var bool whether to gzip the resulting files or not
     */
    private $useGzip = false;

    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @var resource for writable incremental deflate context
     */
    private $deflateContext;

    /**
     * @var resource for php://temp stream
     */
    private $tempFile;

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
     * Get array of generated files
     * @return array
     */
    public function getWrittenFilePath()
    {
        return $this->writtenFilePaths;
    }
    
    /**
     * Creates new file
     * @throws \RuntimeException if file is not writeable
     */
    private function createNewFile()
    {
        $this->fileCount++;
        $filePath = $this->getCurrentFilePath();
        $this->writtenFilePaths[] = $filePath;

        if (file_exists($filePath)) {
            $filePath = realpath($filePath);
            if (is_writable($filePath)) {
                unlink($filePath);
            } else {
                throw new \RuntimeException("File \"$filePath\" is not writable.");
            }
        }

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent($this->useIndent);
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
            $this->flush(true);
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
     * @param bool $finishFile Pass true to close the file to write to, used only when useGzip is true
     */
    private function flush($finishFile = false)
    {
        if ($this->useGzip) {
            $this->flushGzip($finishFile);
            return;
        }
        file_put_contents($this->getCurrentFilePath(), $this->writer->flush(true), FILE_APPEND);
    }

    /**
     * Decides how to flush buffer into compressed file
     * @param bool $finishFile Pass true to close the file to write to
     */
    private function flushGzip($finishFile = false) {
        if (function_exists('deflate_init') && function_exists('deflate_add')) {
            $this->flushWithIncrementalDeflate($finishFile);
            return;
        }
        $this->flushWithTempFileFallback($finishFile);
    }

    /**
     * Flushes buffer into file with incremental deflating data, available in php 7.0+
     * @param bool $finishFile Pass true to write last chunk with closing headers
     */
    private function flushWithIncrementalDeflate($finishFile = false) {
        $flushMode = $finishFile ? ZLIB_FINISH : ZLIB_NO_FLUSH;

        if (empty($this->deflateContext)) {
            $this->deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
        }
        
        $compressedChunk = deflate_add($this->deflateContext, $this->writer->flush(true), $flushMode);
        file_put_contents($this->getCurrentFilePath(), $compressedChunk, FILE_APPEND);

        if ($finishFile) {
            $this->deflateContext = null;
        }
    }

    /**
     * Flushes buffer into temporary stream and compresses stream into a file on finish
     * @param bool $finishFile Pass true to compress temporary stream into desired file
     */
    private function flushWithTempFileFallback($finishFile = false) {
        if (empty($this->tempFile) || !is_resource($this->tempFile)) {
            $this->tempFile = fopen('php://temp/', 'w');
        }

        fwrite($this->tempFile, $this->writer->flush(true));

        if ($finishFile) {
            $file = fopen('compress.zlib://' . $this->getCurrentFilePath(), 'w');
            rewind($this->tempFile);
            stream_copy_to_stream($this->tempFile, $file);
            fclose($file);
            fclose($this->tempFile);
        }
    }

    /**
     * Takes a string and validates, if the string
     * is a valid url
     *
     * @param string $location
     * @throws \InvalidArgumentException
     */
    protected function validateLocation($location) {
        if (false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }
    }
    
    /**
     * Adds a new item to sitemap
     *
     * @param string $location location item URL
     * @param integer $lastModified last modification timestamp
     * @param float $changeFrequency change frequency. Use one of self:: constants here
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

        $this->validateLocation($location);
        
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
            $this->writer->writeElement('priority', number_format($priority, 1, '.', ','));
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
        if ($parts['extension'] === 'gz') {
            $filenameParts = pathinfo($parts['filename']);
            if (!empty($filenameParts['extension'])) {
                $parts['filename'] = $filenameParts['filename'];
                $parts['extension'] = $filenameParts['extension'] . '.gz';
            }
        }
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
        $urls = array();
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
     * Sets if XML should be indented.
     * Default is true.
     *
     * @param bool $value
     */
    public function setUseIndent($value)
    {
        $this->useIndent = (bool)$value;
    }

    /**
     * Sets whether the resulting files will be gzipped or not.
     * @param bool $value
     * @throws \RuntimeException when trying to enable gzip while zlib is not available or when trying to change
     * setting when some items are already written
     */
    public function setUseGzip($value)
    {
        if ($value && !extension_loaded('zlib')) {
            throw new \RuntimeException('Zlib extension must be enabled to gzip the sitemap.');
        }
        if ($this->urlsCount && $value != $this->useGzip) {
            throw new \RuntimeException('Cannot change the gzip value once items have been added to the sitemap.');
        }
        $this->useGzip = $value;
    }
}
