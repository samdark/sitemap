<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Sitemap;

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    protected function assertIsValidSitemap($fileName)
    {
        $xml = new \DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/sitemap.xsd'));
    }

    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_regular.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertTrue(file_exists($fileName));
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    public function testMultipleFiles()
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi.xml');
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = array(
            __DIR__ . '/' .'sitemap_multi.xml',
            __DIR__ . '/' .'sitemap_multi_2.xml',
            __DIR__ . '/' .'sitemap_multi_3.xml',
            __DIR__ . '/' .'sitemap_multi_4.xml',
            __DIR__ . '/' .'sitemap_multi_5.xml',
            __DIR__ . '/' .'sitemap_multi_6.xml',
            __DIR__ . '/' .'sitemap_multi_7.xml',
            __DIR__ . '/' .'sitemap_multi_8.xml',
            __DIR__ . '/' .'sitemap_multi_9.xml',
            __DIR__ . '/' .'sitemap_multi_10.xml',
        );
        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists($expectedFile), "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertEquals(10, count($urls), print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi.xml', $urls);
        $this->assertContains('http://example.com/sitemap_multi_10.xml', $urls);
    }

    public function testFrequencyValidation()
    {
        $this->setExpectedException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time(), 'invalid');

        unlink($fileName);
    }

    public function testPriorityValidation()
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/mylink1');
            $sitemap->addItem('http://example.com/mylink2', time(), 'always', 2.0);
        } catch (\InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testLocationValidation()
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/mylink1');
            $sitemap->addItem('notlink', time());
        } catch (\InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testWritingFileGzipped()
    {
        $fileName = __DIR__ . '/sitemap_gzipped.xml.gz';
        $sitemap = new Sitemap($fileName);
        $sitemap->setGzip(true);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertTrue(file_exists($fileName));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('application/x-gzip', $finfo->file($fileName));
        $this->assertIsValidSitemap('compress.zlib://' . $fileName);

        unlink($fileName);
    }

    public function testMultipleFilesGzipped()
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi_gzipped.xml.gz');
        $sitemap->setGzip(true);
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/' .'sitemap_multi_gzipped.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_2.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_3.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_4.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_5.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_6.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_7.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_8.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_9.xml.gz',
            __DIR__ . '/' .'sitemap_multi_gzipped_10.xml.gz',
        ];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists($expectedFile), "$expectedFile does not exist!");
            $this->assertEquals('application/x-gzip', $finfo->file($expectedFile));
            $this->assertIsValidSitemap('compress.zlib://' . $expectedFile);
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertEquals(10, count($urls), print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi_gzipped.xml.gz', $urls);
        $this->assertContains('http://example.com/sitemap_multi_gzipped_10.xml.gz', $urls);
    }
}
