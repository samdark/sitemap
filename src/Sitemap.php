<?php
namespace samdark\sitemap;

use InvalidArgumentException;
use OverflowException;
use RuntimeException;
use Throwable;
use XMLWriter;

/**
 * A class for generating Sitemaps (http://www.sitemaps.org/)
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Sitemap
{
    use UrlEncoderTrait;
    public const ALWAYS = 'always';
    public const HOURLY = 'hourly';
    public const DAILY = 'daily';
    public const WEEKLY = 'weekly';
    public const MONTHLY = 'monthly';
    public const YEARLY = 'yearly';
    public const NEVER = 'never';

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
     * @var ?string Path of the XML stylesheet.
     */
    private $stylesheet = null;

    /**
     * @var integer number of files written
     */
    private $fileCount = 0;

    /**
     * @var list<string> Paths of files written.
     */
    private $writtenFilePaths = [];

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
    private $useXhtml;

    /**
     * @var list<string> Valid values for frequency parameter.
     */
    private $validFrequencies = [
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER,
    ];

    /**
     * @var array<string, true> Valid values for frequency parameter as map.
     */
    private $validFrequenciesMap = [
        self::ALWAYS => true,
        self::HOURLY => true,
        self::DAILY => true,
        self::WEEKLY => true,
        self::MONTHLY => true,
        self::YEARLY => true,
        self::NEVER => true,
    ];

    /**
     * @var array<string, string> Formatted priority values.
     */
    private $formattedPriorities = [];

    /**
     * @var bool whether to gzip the resulting files or not.
     */
    private $useGzip = false;

    /**
     * @var ?WriterInterface That does the actual writing.
     */
    private $writerBackend = null;

    /**
     * @var ?XMLWriter
     */
    private $writer = null;

    /**
     * @param string $filePath path of the file to write to
     * @param bool $useXhtml is XHTML namespace should be specified
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $filePath, bool $useXhtml = false)
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            throw new InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }

        $this->filePath = $filePath;
        $this->useXhtml = $useXhtml;
    }

    /**
     * Get array of generated files.
     * @return list<string> Generated files.
     */
    public function getWrittenFilePath(): array
    {
        return $this->writtenFilePaths;
    }
    
    /**
     * Creates new file.
     * @throws RuntimeException If file is not writeable.
     */
    private function createNewFile(): void
    {
        $this->fileCount++;
        $filePath = $this->getCurrentFilePath();
        $this->writtenFilePaths[] = $filePath;

        if (file_exists($filePath)) {
            $filePath = realpath($filePath);
            if ($filePath === false || !is_writable($filePath)) {
                throw new RuntimeException("File \"$filePath\" is not writable.");
            }

            unlink($filePath);
        }

        if ($this->useGzip) {
            if (function_exists('deflate_init') && function_exists('deflate_add')) {
                $this->writerBackend = new DeflateWriter($filePath);
            } else {
                // @codeCoverageIgnoreStart
                $this->writerBackend = new TempFileGZIPWriter($filePath);
                // @codeCoverageIgnoreEnd
            }
        } else {
            $this->writerBackend = new PlainFileWriter($filePath);
        }

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        // Use XML stylesheet, if available
        if ($this->stylesheet !== null) {
            $this->writer->writePi('xml-stylesheet', "type=\"text/xsl\" href=\"" . $this->stylesheet . "\"");
            $this->writer->writeRaw("\n");            
        }
        $this->writer->setIndent($this->useIndent);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        if ($this->useXhtml) {
            $this->writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }

        /*
         * XMLWriter does not give us many options, so we must make sure, that
         * the header was written correctly, and we can simply reuse any <url>
         * elements that did not fit into the previous file. (See self::flush)
         */
        $this->writer->text("\n");
        $this->flush(0);
    }

    /**
     * Writes closing tags to current file
     */
    private function finishFile(): void
    {
        if ($this->writer === null || $this->writerBackend === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $this->writer->endElement();
        $this->writer->endDocument();

        /* To prevent infinite recursion through flush */
        $this->urlsCount = 0;

        $this->flush(0);
        $this->writerBackend->finish();
        $this->writerBackend = null;

        $this->byteCount = 0;
        $this->writer = null;
    }

    /**
     * Finishes writing
     */
    public function write(): void
    {
        if ($this->writer === null) {
            return;
        }

        $this->flush();
        $this->finishFile();
    }

    /**
     * Finishes writing when the object is destroyed
     */
    public function __destruct()
    {
        try {
            $this->write();
        } catch (Throwable $e) {
            // Exceptions must not propagate out of __destruct()
        }
    }

    /**
     * Flushes buffer into file
     *
     * @param int $footSize Size of the remaining closing tags
     * @return bool is new file created
     * @throws OverflowException
     */
    private function flush(int $footSize = 10): bool
    {
        if ($this->writer === null || $this->writerBackend === null) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        $isNewFileCreated = false;
        /** @var string $data */
        $data = $this->writer->flush();
        $dataSize = mb_strlen($data, '8bit');

        /*
         * Limit the file size of each single site map
         *
         * We use a heuristic of 10 Bytes for the remainder of the file,
         * i.e. </urlset> plus a new line.
         */
        if ($this->byteCount + $dataSize + $footSize > $this->maxBytes) {
            if ($this->urlsCount <= 1) {
                throw new OverflowException('The buffer size is too big for the defined file size limit');
            }
            $this->finishFile();
            $this->createNewFile();
            $isNewFileCreated = true;
        }

        $writerBackend = $this->writerBackend;
        if ($writerBackend === null) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Writer backend was not initialized.');
            // @codeCoverageIgnoreEnd
        }

        $writerBackend->append($data);
        $this->byteCount += $dataSize;

        return $isNewFileCreated;
    }

    /**
     * Takes a string and validates, if the string
     * is a valid URL.
     *
     * @param string $location
     * @throws InvalidArgumentException
     */
    protected function validateLocation(string $location): void
    {
        if (!$this->isValidAsciiHttpLocation($location) && false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                "The location must be a valid URL. You have specified: $location."
            );
        }
    }

    /**
     * @param string $location
     * @return bool
     */
    private function isValidAsciiHttpLocation(string $location): bool
    {
        return preg_match(
            '~^https?://[A-Za-z\d](?:[A-Za-z\d.-]*[A-Za-z\d])?(?::\d+)?(?:/\S*)?(?:\?[^\s#]*)?(?:#\S*)?$~',
            $location
        ) === 1;
    }

    /**
     * Adds a new item to sitemap.
     *
     * @param string|array<string, string> $locations Location item URL(s).
     * @param integer|null $lastModified Last modification timestamp.
     * @param string|null $changeFrequency Change frequency. Use one of self:: constants here.
     * @param string|null $priority Item's priority (0.0-1.0). Default `null` is equal to 0.5.
     *
     * @throws InvalidArgumentException
     */
    public function addItem($locations, ?int $lastModified = null, ?string $changeFrequency = null, ?string $priority = null): void
    {
        $isMultiLanguage = is_array($locations);
        $delta = $isMultiLanguage ? count($locations) : 1;
        if ($lastModified !== null) {
            $lastModified = date('c', $lastModified);
        }
        if ($changeFrequency !== null) {
            $this->validateChangeFrequency($changeFrequency);
        }
        if ($priority !== null) {
            $priority = $this->formatPriority($priority);
        }

        if (($this->urlsCount + $delta) > $this->maxUrls && $this->writer !== null) {
            $isNewFileCreated = $this->flush();
            if (!$isNewFileCreated) {
                $this->finishFile();
            }
        }

        if ($this->writerBackend === null) {
            $this->createNewFile();
        }

        if ($isMultiLanguage) {
            $this->addMultiLanguageItem($locations, $lastModified, $changeFrequency, $priority);
        } else {
            $this->addSingleLanguageItem($locations, $lastModified, $changeFrequency, $priority);
        }

        $prevCount = $this->urlsCount;
        $this->urlsCount += $delta;

        if (
            $this->bufferSize > 0
            && (int) ($prevCount / $this->bufferSize) !== (int) ($this->urlsCount / $this->bufferSize)
        ) {
            $this->flush();
        }
    }


    /**
     * Adds a new single item to sitemap.
     *
     * @param string $location Location item URL.
     * @param ?string $lastModified Formatted last modification timestamp.
     * @param ?string $changeFrequency Change frequency. Use one of self:: constants here.
     * @param ?string $priority Item's priority (0.0-1.0). Default `null` is equal to 0.5.
     *
     * @throws InvalidArgumentException
     *
     * @see addItem
     */
    private function addSingleLanguageItem(string $location, ?string $lastModified, ?string $changeFrequency, ?string $priority): void
    {
        $writer = $this->writer;
        if ($writer === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $location = $this->encodeUrl($location);

        $this->validateLocation($location);


        $writer->startElement('url');

        $writer->writeElement('loc', $location);

        if ($lastModified !== null) {
            $writer->writeElement('lastmod', $lastModified);
        }

        if ($changeFrequency !== null) {
            $writer->writeElement('changefreq', $changeFrequency);
        }

        if ($priority !== null) {
            $writer->writeElement('priority', $priority);
        }

        $writer->endElement();
    }

    /**
     * Adds a multi-language item, based on multiple locations with alternate hrefs to sitemap.
     *
     * @param array<string, string> $locations Locations. Array of language => link pairs.
     * @param ?string $lastModified Formatted last modification timestamp.
     * @param ?string $changeFrequency Change frequency. Use one of self:: constants here.
     * @param ?string $priority item's priority (0.0-1.0). Default null is equal to 0.5.
     *
     * @throws InvalidArgumentException
     *
     * @see addItem
     */
    private function addMultiLanguageItem(array $locations, ?string $lastModified, ?string $changeFrequency, ?string $priority): void
    {
        $writer = $this->writer;
        if ($writer === null) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $encodedLocations = [];
        foreach ($locations as $language => $url) {
            $encodedUrl = $this->encodeUrl($url);
            $this->validateLocation($encodedUrl);
            $encodedLocations[$language] = $encodedUrl;
        }

        foreach ($encodedLocations as $url) {
            $writer->startElement('url');

            $writer->writeElement('loc', $url);

            if ($lastModified !== null) {
                $writer->writeElement('lastmod', $lastModified);
            }

            if ($changeFrequency !== null) {
                $writer->writeElement('changefreq', $changeFrequency);
            }

            if ($priority !== null) {
                $writer->writeElement('priority', $priority);
            }

            foreach ($encodedLocations as $hreflang => $href) {
                $writer->startElement('xhtml:link');
                $writer->writeAttribute('rel', 'alternate');
                $writer->writeAttribute('hreflang', $hreflang);
                $writer->writeAttribute('href', $href);
                $writer->endElement();
            }

            $writer->endElement();
        }
    }

    /**
     * @param string|null $changeFrequency
     */
    private function validateChangeFrequency(?string $changeFrequency): void
    {
        if (!isset($this->validFrequenciesMap[$changeFrequency])) {
            throw new InvalidArgumentException(
                'Please specify valid changeFrequency. Valid values are: '
                . implode(', ', $this->validFrequencies)
                . ". You have specified: $changeFrequency."
            );
        }
    }

    /**
     * @param string $priority
     * @return string
     */
    private function formatPriority(string $priority): string
    {
        if (!is_numeric($priority) || $priority < 0 || $priority > 1) {
            throw new InvalidArgumentException(
                "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: \"$priority\"."
            );
        }

        $key = 'priority:' . $priority;
        if (!array_key_exists($key, $this->formattedPriorities)) {
            $this->formattedPriorities[$key] = number_format((float)$priority, 1);
        }

        return $this->formattedPriorities[$key];
    }


    /**
     * @return string path of currently opened file
     */
    private function getCurrentFilePath(): string
    {
        return $this->buildCurrentFilePath($this->filePath, $this->fileCount);
    }

    /**
     * Hook for customizing the path of the currently opened file.
     *
     * @param string $filePath base file path
     * @param integer $fileCount number of files written
     * @return string path of currently opened file
     */
    protected function buildCurrentFilePath(string $filePath, int $fileCount): string
    {
        if ($fileCount < 2) {
            return $filePath;
        }

        /**
         * @var array{dirname: string, basename: string, extension: string, filename: string} $parts
         */
        $parts = pathinfo($filePath);
        if ($parts['extension'] === 'gz') {
            $filenameParts = pathinfo($parts['filename']);
            if (!empty($filenameParts['extension'])) {
                $parts['filename'] = $filenameParts['filename'];
                $parts['extension'] = $filenameParts['extension'] . '.gz';
            }
        }
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '_' . $fileCount . '.' . $parts['extension'];
    }

    /**
     * Returns an array of URLs written
     *
     * @param string $baseUrl Base URL of all the sitemaps written.
     * @return list<string> URLs of sitemaps written.
     */
    public function getSitemapUrls(string $baseUrl): array
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
    public function setMaxUrls(int $number): void
    {
        $this->maxUrls = $number;
    }

    /**
     * Sets maximum number of bytes to write in a single file.
     * Default is 10485760 or 10 MiB.
     * @param integer $number
     */
    public function setMaxBytes(int $number): void
    {
        $this->maxBytes = $number;
    }

    /**
     * Sets number of URLs to be kept in memory before writing it to file.
     * Default is 10.
     *
     * @param integer $number
     */
    public function setBufferSize(int $number): void
    {
        $this->bufferSize = $number;
    }


    /**
     * Sets if XML should be indented.
     * Default is true.
     *
     * @param bool $value
     */
    public function setUseIndent(bool $value): void
    {
        $this->useIndent = $value;
    }

    /**
     * Sets whether the resulting files will be gzipped or not.
     * @param bool $value
     * @throws RuntimeException when trying to enable gzip while zlib is not available or when trying to change
     * setting when some items are already written
     */
    public function setUseGzip(bool $value): void
    {
        if ($value && !extension_loaded('zlib')) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Zlib extension must be enabled to gzip the sitemap.');
            // @codeCoverageIgnoreEnd
        }
        if ($this->writerBackend !== null && $value !== $this->useGzip) {
            throw new RuntimeException('Cannot change the gzip value once items have been added to the sitemap.');
        }
        $this->useGzip = $value;
    }

    /**
     * Sets stylesheet for the XML file.
     * Default is to not generate XML stylesheet tag.
     * @param string $stylesheetUrl Stylesheet URL.
     */
    public function setStylesheet(string $stylesheetUrl): void
    {
        if (false === filter_var($stylesheetUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(
                "The stylesheet URL is not valid. You have specified: {$stylesheetUrl}."
            );
        }

        $this->stylesheet = $stylesheetUrl;
    }
}
