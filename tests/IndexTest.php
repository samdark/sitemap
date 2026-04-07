<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;

class IndexTest extends \PHPUnit\Framework\TestCase
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
        $this->expectException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap.xml';
        $index = new Index($fileName);
        $index->addSitemap('noturl');

        unlink($fileName);
    }

    public function testStylesheetIsIncludedInOutput()
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

    public function testStylesheetInvalidUrlThrowsException()
    {
        $this->expectException('InvalidArgumentException');

        $index = new Index(__DIR__ . '/sitemap_index.xml');
        $index->setStylesheet('not-a-valid-url');
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
        $this->assertMatchesRegularExpression('!application/(x-)?gzip!', $finfo->file($fileName));
        $this->assertIsValidIndex('compress.zlib://' . $fileName);
        unlink($fileName);
    }

    public function testAutoDetectLastModified()
    {
        // Create a test sitemap file
        $sitemapFile = __DIR__ . '/test_sitemap_for_autodetect.xml';
        file_put_contents($sitemapFile, '<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>');

        // Get the file modification time
        $expectedTime = filemtime($sitemapFile);

        // Create index and add sitemap with auto-detection
        $indexFile = __DIR__ . '/sitemap_index_autodetect.xml';
        $index = new Index($indexFile);
        $index->addSitemap('http://example.com/test_sitemap.xml', null, $sitemapFile);
        $index->write();

        // Read the generated index file
        $content = file_get_contents($indexFile);

        // Check that lastmod element exists
        $this->assertStringContainsString('<lastmod>', $content);

        // Parse and validate the date
        $xml = new \DOMDocument();
        $xml->load($indexFile);
        $lastmodNodes = $xml->getElementsByTagName('lastmod');
        $this->assertEquals(1, $lastmodNodes->length);

        $lastmodValue = $lastmodNodes->item(0)->nodeValue;
        $parsedTime = strtotime($lastmodValue);

        // The times should match (within a 2 second tolerance for filesystem differences)
        $this->assertEqualsWithDelta($expectedTime, $parsedTime, 2);

        // Validate the index
        $this->assertIsValidIndex($indexFile);

        // Cleanup
        unlink($sitemapFile);
        unlink($indexFile);
    }

    public function testAutoDetectLastModifiedWithNonExistentFile()
    {
        // Test that if file doesn't exist, no lastmod is added
        $indexFile = __DIR__ . '/sitemap_index_nofile.xml';
        $index = new Index($indexFile);
        $index->addSitemap('http://example.com/test_sitemap.xml', null, '/nonexistent/file.xml');
        $index->write();

        // Read the generated index file
        $content = file_get_contents($indexFile);

        // Check that lastmod element does NOT exist
        $this->assertStringNotContainsString('<lastmod>', $content);

        // Validate the index
        $this->assertIsValidIndex($indexFile);

        // Cleanup
        unlink($indexFile);
    }
}
