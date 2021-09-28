<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{
    protected function assertIsValidIndex($fileName)
    {
        $xml = new \DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/siteindex.xsd'));
    }

    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_index.xml';
        $index = new Index($fileName);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertTrue(file_exists($fileName));
        $this->assertIsValidIndex($fileName);
        unlink($fileName);
    }

    public function testLocationValidation()
    {
        $this->setExpectedException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap.xml';
        $index = new Index($fileName);
        $index->addSitemap('noturl');

        unlink($fileName);
    }

    public function testWritingFileGzipped()
    {
        $fileName = __DIR__ . '/sitemap_index.xml.gz';
        $index = new Index($fileName);
        $index->setUseGzip(true);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertTrue(file_exists($fileName));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('application/x-gzip', $finfo->file($fileName));
        $this->assertIsValidIndex('compress.zlib://' . $fileName);
        unlink($fileName);
    }
}
