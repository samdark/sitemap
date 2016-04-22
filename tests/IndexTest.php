<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $fileName
     */
    protected function assertIsValidIndex($fileName)
    {
        $xml = new \DOMDocument();
        $xml->load($fileName);
        static::assertTrue($xml->schemaValidate(__DIR__ . '/siteindex.xsd'));
    }

    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_index.xml';
        $index = new Index($fileName);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        static::assertFileExists($fileName);
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
}
