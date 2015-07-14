<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;

class IndexTest extends \PHPUnit_Framework_TestCase
{
    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_index.xml';
        $index = new Index($fileName);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $this->assertTrue(file_exists($fileName));
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
}
