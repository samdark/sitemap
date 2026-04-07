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
    private $useGzip = false;

    /**
     * @param string $filePath index file path
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @var string path of the xml stylesheet
     */
    private $stylesheet;

    /**
     * Creates new file
     */
    private function createNewFile()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        // Use XML stylesheet, if available
        if (isset($this->stylesheet)) {
            $this->writer->writePi('xml-stylesheet', "type=\"text/xsl\" href=\"" . $this->stylesheet . "\"");
            $this->writer->writeRaw("\n");            
        }
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
        // Encode the URL to handle international characters
        $location = $this->encodeUrl($location);

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
     * Encodes a URL to ensure international characters are properly percent-encoded
     * according to RFC 3986 while avoiding double-encoding
     *
     * @param string $url the URL to encode
     * @return string the encoded URL
     */
    private function encodeUrl($url)
    {
        // Parse the URL into components
        $parsed = parse_url($url);

        if ($parsed === false) {
            // If parse_url fails, return the original URL
            return $url;
        }

        $encoded = '';

        // Scheme (http, https, etc.)
        if (isset($parsed['scheme'])) {
            $encoded .= $parsed['scheme'] . '://';
        }

        // Host (domain)
        if (isset($parsed['host'])) {
            // For international domain names (IDN), we should use idn_to_ascii
            // However, if it's already ASCII, idn_to_ascii will return it as-is
            if (function_exists('idn_to_ascii')) {
                // Use INTL_IDNA_VARIANT_UTS46 if available (PHP 7.2+), otherwise use default
                $host = defined('INTL_IDNA_VARIANT_UTS46')
                    ? idn_to_ascii($parsed['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)
                    : idn_to_ascii($parsed['host']);
                $encoded .= $host !== false ? $host : $parsed['host'];
            } else {
                $encoded .= $parsed['host'];
            }
        }

        // Port
        if (isset($parsed['port'])) {
            $encoded .= ':' . $parsed['port'];
        }

        // Path
        if (isset($parsed['path'])) {
            // Split path into segments to encode each segment separately
            $pathSegments = explode('/', $parsed['path']);
            $encodedSegments = array();

            foreach ($pathSegments as $segment) {
                if ($segment === '') {
                    $encodedSegments[] = '';
                } else {
                    // Only encode if the segment contains non-ASCII characters
                    // Check if segment has any non-ASCII characters
                    if (preg_match('/[^\x20-\x7E]/', $segment)) {
                        // Has non-ASCII, needs encoding
                        $encodedSegments[] = rawurlencode($segment);
                    } else {
                        // Already ASCII, check if it's already percent-encoded
                        $decoded = rawurldecode($segment);
                        if ($decoded !== $segment) {
                            // It was already encoded, keep it as-is
                            $encodedSegments[] = $segment;
                        } else {
                            // Not encoded, but is ASCII, keep as-is
                            $encodedSegments[] = $segment;
                        }
                    }
                }
            }
            $encoded .= implode('/', $encodedSegments);
        }

        // Query string - just check for non-ASCII characters
        if (isset($parsed['query'])) {
            $query = $parsed['query'];
            // Only encode non-ASCII characters in the query string
            if (preg_match('/[^\x20-\x7E]/', $query)) {
                // Has non-ASCII characters, encode them while preserving structure
                // Split by & to process each parameter
                $parts = explode('&', $query);
                $encodedParts = array();
                foreach ($parts as $part) {
                    if (strpos($part, '=') !== false) {
                        list($key, $value) = explode('=', $part, 2);
                        // Only encode if there are non-ASCII characters
                        if (preg_match('/[^\x20-\x7E]/', $key)) {
                            $key = rawurlencode($key);
                        }
                        if (preg_match('/[^\x20-\x7E]/', $value)) {
                            $value = rawurlencode($value);
                        }
                        $encodedParts[] = $key . '=' . $value;
                    } else {
                        // No = sign, just encode if needed
                        if (preg_match('/[^\x20-\x7E]/', $part)) {
                            $encodedParts[] = rawurlencode($part);
                        } else {
                            $encodedParts[] = $part;
                        }
                    }
                }
                $encoded .= '?' . implode('&', $encodedParts);
            } else {
                // No non-ASCII, keep as-is
                $encoded .= '?' . $query;
            }
        }

        // Fragment
        if (isset($parsed['fragment'])) {
            $fragment = $parsed['fragment'];
            // Only encode if there are non-ASCII characters
            if (preg_match('/[^\x20-\x7E]/', $fragment)) {
                $encoded .= '#' . rawurlencode($fragment);
            } else {
                $encoded .= '#' . $fragment;
            }
        }

        return $encoded;
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
            if ($this->useGzip) {
                $filePath = 'compress.zlib://' . $filePath;
            }
            file_put_contents($filePath, $this->writer->flush());
        }
    }

    /**
     * Sets whether the resulting file will be gzipped or not.
     * @param bool $value
     * @throws \RuntimeException when trying to enable gzip while zlib is not available
     */
    public function setUseGzip($value)
    {
        if ($value && !extension_loaded('zlib')) {
            throw new \RuntimeException('Zlib extension must be installed to gzip the sitemap.');
        }
        $this->useGzip = $value;
    }

    /**
     * Sets stylesheet for the XML file.
     * Default is to not generate XML-stylesheet tag.
     * @param string $stylesheetUrl Stylesheet URL.
     */
    public function setStylesheet($stylesheetUrl)
    {
        if (false === filter_var($stylesheetUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                "The stylesheet URL is not valid. You have specified: {$stylesheetUrl}."
            );
        } else {
            $this->stylesheet = $stylesheetUrl;
        }
    }
}