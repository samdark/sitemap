<?php
namespace SamDark\Sitemap;

use SamDark\Sitemap\Writer\DeflateWriter;
use SamDark\Sitemap\Writer\PlainFileWriter;
use Samdark\Sitemap\Writer\TempFileGZIPWriter;
use SamDark\Sitemap\Writer\WriterInterface;
use XMLWriter;

/**
 * A class for generating Sitemaps (http://www.sitemaps.org/)
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Sitemap
{
    /**
     * @var integer Maximum allowed number of URLs in a single file.
     */
    protected $maxUrls = 50000;

    /**
     * @var integer number of URLs added
     */
    protected $urlsCount = 0;

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
    protected $fileCount = 0;

    /**
     * @var array path of files written
     */
    protected $writtenFilePaths = [];

    /**
     * @var integer number of URLs to be kept in memory before writing it to file
     */
    protected $bufferSize = 10;

    /**
     * @var bool if XML should be indented
     */
    protected $useIndent = true;

    /**
     * @var bool whether to gzip the resulting files or not
     */
    protected $useGzip = false;

    /**
     * @var WriterInterface that does the actual writing
     */
    protected $writerBackend;

    /**
     * @var XMLWriter
     */
    protected $writer;

    private $extensionClasses;

    /**
     * @param string $filePath path of the file to write to
     * @param array $extensionClasses
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($filePath, array $extensionClasses = [])
    {
        $dir = \dirname($filePath);
        if (!is_dir($dir)) {
            throw new \InvalidArgumentException(
                "Please specify valid file path. Directory not exists. You have specified: {$dir}."
            );
        }

        $this->filePath = $filePath;
        $this->extensionClasses = $extensionClasses;
    }

    /**
     * Get array of generated files
     * @return array
     */
    public function getWrittenFilePaths(): array
    {
        return $this->writtenFilePaths;
    }
    
    /**
     * Creates new file
     * @throws \RuntimeException if file is not writeable
     */
    protected function createNewFile(): void
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
            if (\function_exists('deflate_init') && \function_exists('deflate_add')) {
                $this->writerBackend = new DeflateWriter($filePath);
            } else {
                $this->writerBackend = new TempFileGZIPWriter($filePath);
            }
        } else {
            $this->writerBackend = new PlainFileWriter($filePath);
        }

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->addHeader();

        /*
         * XMLWriter does not give us much options, so we must make sure, that
         * the header was written correctly and we can simply reuse any <url>
         * elements that did not fit into the previous file. (See self::flush)
         */
        $this->writer->text(PHP_EOL);
        $this->flush();
    }

    /**
     * Writes closing tags to current file
     * @throws \RuntimeException
     * @throws \OverflowException
     */
    protected function finishFile(): void
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
     * @throws \RuntimeException
     * @throws \OverflowException
     */
    public function write(): void
    {
        $this->finishFile();
    }

    /**
     * Flushes buffer into file
     *
     * @param int $footSize Size of the remaining closing tags
     * @throws \RuntimeException
     * @throws \OverflowException
     */
    protected function flush($footSize = 10): void
    {
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
                throw new \OverflowException('The buffer size is too big for the defined file size limit');
            }
            $this->finishFile();
            $this->createNewFile();
        }

        $this->writerBackend->append($data);
        $this->byteCount += $dataSize;
    }

    /**
     * Adds a new URL to sitemap
     *
     * @param Url $url
     * @throws \OverflowException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function addUrl(Url $url): void
    {
        if ($this->urlsCount >= $this->maxUrls) {
            $this->finishFile();
        }

        if ($this->writerBackend === null) {
            $this->createNewFile();
        }

        $this->writeUrl($url);

        $this->urlsCount++;

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }
    }

    /**
     * Writes XML for Url passed
     * @param Url $url
     * @throws \LogicException
     */
    protected function writeUrl(Url $url): void
    {
        $this->writer->startElement('url');
        $this->writer->writeElement('loc', $url->getLocation());

        if ($url->getLastModified() !== null) {
            $this->writer->writeElement('lastmod', $url->getLastModified()->format('c'));
        }

        if ($url->getChangeFrequency() !== null) {
            $this->writer->writeElement('changefreq', $url->getChangeFrequency());
        }

        $this->writer->writeElement('priority', number_format($url->getPriority(), 1));

        foreach ($url->getExtensionItems() as $item) {
            $extensionClass = \get_class($item);
            if (!\in_array($extensionClass, $this->extensionClasses, true)) {
                throw new \LogicException("$extensionClass is missing from an array of extension class names passed as second Sitemap constructor argument.");
            }
            $item->write($this->writer);
        }

        $this->writer->endElement();
    }

    /**
     * @return string path of currently opened file
     */
    protected function getCurrentFilePath(): string
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
        return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '-' . $this->fileCount . '.' . $parts['extension'];
    }

    /**
     * Returns an array of URLs written
     *
     * @param string $baseUrl base URL of all the sitemaps written
     * @return array URLs of sitemaps written
     */
    public function getSitemapUrls($baseUrl): array
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
    public function setMaxUrls($number): void
    {
        $this->maxUrls = (int)$number;
    }

    /**
     * Sets maximum number of bytes to write in a single file.
     * Default is 10485760 or 10 MiB.
     * @param integer $number
     */
    public function setMaxBytes($number): void
    {
        $this->maxBytes = (int)$number;
    }

    /**
     * Sets number of URLs to be kept in memory before writing it to file.
     * Default is 10.
     *
     * @param integer $number
     */
    public function setBufferSize($number): void
    {
        $this->bufferSize = (int)$number;
    }

    /**
     * Sets if XML should be indented.
     * Default is true.
     *
     * @param bool $value
     */
    public function setUseIndent($value): void
    {
        $this->useIndent = (bool)$value;
    }

    /**
     * Sets whether the resulting files will be gzipped or not.
     * @param bool $value
     * @throws \RuntimeException when trying to enable gzip while zlib is not available or when trying to change
     * setting when some items are already written
     */
    public function setUseGzip($value): void
    {
        if ($value && !\extension_loaded('zlib')) {
            throw new \RuntimeException('Zlib extension must be enabled to gzip the sitemap.');
        }
        if ($this->writerBackend !== null && $value !== $this->useGzip) {
            throw new \RuntimeException('Cannot change the gzip value once items have been added to the sitemap.');
        }
        $this->useGzip = $value;
    }

    /**
     * Adds a document header
     */
    protected function addHeader(): void
    {
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent($this->useIndent);
        $this->writer->startElement('urlset');
        $this->writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->extensionClasses as $extensionClass) {
            $extensionClass::writeXmlNamepsace($this->writer);
        }
    }
}
