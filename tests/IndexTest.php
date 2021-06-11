<?php

namespace SamDark\Sitemap\tests;

use SamDark\Sitemap\Index;

/**
 * IndexTest tests Sitemap index generator
 */
class IndexTest extends TestCase
{
    public function testWritingFile()
    {
        $fileName = $this->getTempPath('sitemap_index.xml');
        $index = new Index($fileName);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertFileExists($fileName);
        $this->assertValidXml($fileName, 'index');
    }

    public function testWritingFileGzipped()
    {
        $fileName = $this->getTempPath('sitemap_index.xml.gz');
        $index = new Index($fileName);
        $index->setUseGzip(true);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertFileExists($fileName);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        $this->assertRegExp('!application/(x-)?gzip!', $finfo->file($fileName));
        $this->assertValidXml('compress.zlib://' . $fileName, 'index');
    }
}
