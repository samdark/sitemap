<?php
namespace samdark\sitemap;

use XMLWriter;

/**
 * A class for generating image Sitemaps (http://www.sitemaps.org/)
 *
 * @author SunwelLight
 */
class SitemapImages extends Sitemap
{
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
        $this->writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
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
     * Adds a new item to sitemap images
     *
     * @param string|array $location location item URL
     * @param array $images image array page
     *
     * @throws \InvalidArgumentException
     */
    public function addImage($location, $images)
    {
        if ($this->urlsCount >= $this->maxUrls) {
            $this->finishFile();
        }

        if ($this->writerBackend === null) {
            $this->createNewFile();
        }

        $this->addGroupingImage($location, $images);

        $this->urlsCount++;

        if ($this->urlsCount % $this->bufferSize === 0) {
            $this->flush();
        }
    }

    /**
     * Adds a new single item to sitemap images
     *
     * @param string $location location item URL
     * @param array $images image array page
     *
     * @throws \InvalidArgumentException
     *
     * @see addItem
     */
    private function addGroupingImage($location, $images)
    {
        $this->validateLocation($location);


        $this->writer->startElement('url');

        $this->writer->writeElement('loc', $location);

        if(is_array($images)) {
            foreach ($images AS $image) {
                $this->writer->startElement('image:image');

                if(!empty($image['loc'])) {
                    $this->writer->writeElement('image:loc', $image['loc']);
                }
                if(!empty($image['caption'])) {
                    $this->writer->writeElement('image:caption', $image['caption']);
                }
                if(!empty($image['geo_location'])) {
                    $this->writer->writeElement('image:geo_location', $image['geo_location']);
                }
                if(!empty($image['title'])) {
                    $this->writer->writeElement('image:title', $image['title']);
                }
                if(!empty($image['license'])) {
                    $this->writer->writeElement('image:license', $image['license']);
                }

                $this->writer->endElement();
            }
        }

        $this->writer->endElement();
    }
}
