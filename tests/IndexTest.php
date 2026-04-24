<?php
namespace samdark\sitemap\tests;

use DOMDocument;
use finfo;
use samdark\sitemap\Index;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    protected function assertIsValidIndex(string $fileName): void
    {
        $xml = new DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/siteindex.xsd'));
    }

    public function testWritingFile(): void
    {
        $fileName = __DIR__ . '/sitemap_index.xml';
        $index = new Index($fileName);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertFileExists($fileName);
        $this->assertIsValidIndex($fileName);
        unlink($fileName);
    }

    public function testLocationValidation(): void
    {
        $this->expectException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap.xml';
        $index = new Index($fileName);
        $index->addSitemap('http://example.com:bad/é');

        unlink($fileName);
    }

    public function testStylesheetIsIncludedInOutput(): void
    {
        $fileName = __DIR__ . '/sitemap_index_stylesheet.xml';
        $index = new Index($fileName);
        $index->setStylesheet('http://example.com/sitemap.xsl');
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->write();

        $this->assertFileExists($fileName);
        $content = file_get_contents($fileName);
        $this->assertStringContainsString('<?xml-stylesheet', $content);
        $this->assertStringContainsString('type="text/xsl"', $content);
        $this->assertStringContainsString('href="http://example.com/sitemap.xsl"', $content);
        $this->assertIsValidIndex($fileName);

        unlink($fileName);
    }

    public function testStylesheetInvalidUrlThrowsException(): void
    {
        $this->expectException('InvalidArgumentException');

        $index = new Index(__DIR__ . '/sitemap_index.xml');
        $index->setStylesheet('not-a-valid-url');
    }

    public function testWritingFileGzipped(): void
    {
        $fileName = __DIR__ . '/sitemap_index.xml.gz';
        $index = new Index($fileName);
        $index->setUseGzip(true);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertFileExists($fileName);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->assertMatchesRegularExpression('!application/(x-)?gzip!', $finfo->file($fileName));
        $this->assertIsValidIndex('compress.zlib://' . $fileName);
        unlink($fileName);
    }

    public function testInternationalUrlEncoding(): void
    {
        $fileName = __DIR__ . '/sitemap_index_international.xml';
        $index = new Index($fileName);

        // Arabic characters in path
        $index->addSitemap('http://example.com/ar/العامل-الماهر/sitemap.xml');

        // Already encoded URL should not be double-encoded
        $index->addSitemap('http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84/sitemap.xml');

        // Query string with non-ASCII characters
        $index->addSitemap('http://example.com/sitemap.xml?lang=中文');

        $index->write();

        $this->assertFileExists($fileName);
        $content = file_get_contents($fileName);

        // Arabic text should be percent-encoded
        $this->assertStringContainsString(
            'http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84-%D8%A7%D9%84%D9%85%D8%A7%D9%87%D8%B1/sitemap.xml',
            $content
        );

        // Already encoded URL should remain the same (no double-encoding)
        $this->assertStringContainsString(
            'http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84/sitemap.xml',
            $content
        );
        $this->assertStringNotContainsString('%25D8', $content);

        // Chinese query value should be percent-encoded
        $this->assertStringContainsString(
            'http://example.com/sitemap.xml?lang=%E4%B8%AD%E6%96%87',
            $content
        );

        $this->assertIsValidIndex($fileName);
        unlink($fileName);
    }
}
