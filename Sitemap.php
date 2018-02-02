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
     * @var integer Maximum allowed number of bytes in a single file.
     */
    private $maxBytes = 10485760;

    /**
     * @var integer number of bytes already written to the current file, before compression
     */
    private $byteCount = 0;

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
    private $bufferSize = 10;

    /**
     * @var bool if XML should be indented
     */
    private $useIndent = true;

    /**
     * @var bool if should XHTML namespace be specified
     * Useful for multi-language sitemap to point crawler to alternate language page via xhtml:link tag.
     * @see https://support.google.com/webmasters/answer/2620865?hl=en
     */
    private $useXhtml = false;

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
     * @var WriterInterface that does the actual writing
     */
    private $writerBackend;

    /**
     * @var XMLWriter
     */
    private $writer;

    /**
     * @param string $filePath path of the file to write to
     * @param bool $useXhtml is XHTML namespace should be specified
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($filePath, $useXhtml = false)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }

        $this->filePath = $filePath;
        $this->useXhtml = $useXhtml;
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

        if ($this->useGzip) {
            if (function_exists('deflate_init') && function_exists('deflate_add')) {
                $this->writerBackend = new DeflateWriter($filePath);
            } else {
                $this->writerBackend = new TempFileGZIPWriter($filePath);
            }
        } else {
            $this->writerBackend = new PlainFileWriter($filePath);
        }

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent($this->useIndent);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        if ($this->useXhtml) {
            $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }

        /*
         * XMLWriter does not give us much options, so we must make sure, that
         * the header was written correctly and we can simply reuse any <url>
         * elements that did not fit into the previous file. (See self::flush)
         */
        $this->writer->text(PHP_EOL);
        $this->flush(true);
    }

    /**
     * Writes closing tags to current file
     */
    private function finishFile()
    {
        if ($this->writer !== null) {
            $this->writer->endElement();
            $this->writer->endDocument();

            /* To prevent infinite recursion through flush */
            $this->urlsCount = 0;

            $this->flush(0);
            $this->writerBackend->finish();
            $this->writerBackend = null;

            $this->byteCount = 0;
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
     *
     * @param int $footSize Size of the remaining closing tags
     * @throws \OverflowException
     */
    private function flush($footSize = 10)
    {
        $data = $this->writer->flush(true);
        $dataSize = mb_strlen($data, '8bit');

        /*
         * Limit the file size of each single site map
         *
         * We use a heuristic of 10 Bytes for the remainder of the file,
         * i.e. </urlset> plus a new line.
         */
        if ($this->byteCount + $dataSize + $footSize > $this->maxBytes) {
            if ($this->urlsCount <= 1) {
                throw new \OverflowException('The buffer size is too big for the defined file size limit');
            }
            $this->finishFile();
            $this->createNewFile();
        }

        $this->writerBackend->append($data);
        $this->byteCount += $dataSize;
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
     * @param string|array $location location item URL
     * @param integer $lastModified last modification timestamp
     * @param string $changeFrequency change frequency. Use one of self:: constants here
     * @param string $priority item's priority (0.0-1.0). Default null is equal to 0.5
     *
     * @throws \InvalidArgumentException
     */
    public function addItem($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        if ($this->urlsCount >= $this->maxUrls) {
            $this->finishFile();
        }

        if ($this->writerBackend === null) {
            $this->createNewFile();
        }

        if (is_array($location)) {
            $this->addMultiLanguageItem($location, $lastModified, $changeFrequency, $priority);
        } else {
            $this->addSingleLanguageItem($location, $lastModified, $changeFrequency, $priority);
        }

        $this->urlsCount++;

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }
    }


    /**
     * Adds a new single item to sitemap
     *
     * @param string $location location item URL
     * @param integer $lastModified last modification timestamp
     * @param float $changeFrequency change frequency. Use one of self:: constants here
     * @param string $priority item's priority (0.0-1.0). Default null is equal to 0.5
     *
     * @throws \InvalidArgumentException
     *
     * @see addItem
     */
    private function addSingleLanguageItem($location, $lastModified, $changeFrequency, $priority)
    {
        $this->validateLocation($location);


        $this->writer->startElement('url');

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
    }

    /**
     * Adds a multi-language item, based on multiple locations with alternate hrefs to sitemap
     *
     * @param array $locations array of language => link pairs
     * @param integer $lastModified last modification timestamp
     * @param float $changeFrequency change frequency. Use one of self:: constants here
     * @param string $priority item's priority (0.0-1.0). Default null is equal to 0.5
     *
     * @throws \InvalidArgumentException
     *
     * @see addItem
     */
    private function addMultiLanguageItem($locations, $lastModified, $changeFrequency, $priority)
    {
        foreach ($locations as $language => $url) {
            $this->validateLocation($url);

            $this->writer->startElement('url');

            $this->writer->writeElement('loc', $url);

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

            foreach ($locations as $hreflang => $href) {

                $this->writer->startElement('xhtml:link');
                $this->writer->startAttribute('rel');
                $this->writer->text('alternate');
                $this->writer->endAttribute();

                $this->writer->startAttribute('hreflang');
                $this->writer->text($hreflang);
                $this->writer->endAttribute();

                $this->writer->startAttribute('href');
                $this->writer->text($href);
                $this->writer->endAttribute();
                $this->writer->endElement();
            }

            $this->writer->endElement();
        }
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
     * Sets maximum number of bytes to write in a single file.
     * Default is 10485760 or 10 MiB.
     * @param integer $number
     */
    public function setMaxBytes($number)
    {
        $this->maxBytes = (int)$number;
    }

    /**
     * Sets number of URLs to be kept in memory before writing it to file.
     * Default is 10.
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
        if ($this->writerBackend !== null && $value != $this->useGzip) {
            throw new \RuntimeException('Cannot change the gzip value once items have been added to the sitemap.');
        }
        $this->useGzip = $value;
    }
}
