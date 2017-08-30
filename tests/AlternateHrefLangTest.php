<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;

class AlternateHrefLangTest extends \PHPUnit_Framework_TestCase
{
    public function testNotArrayLang()
    {
        $this->setExpectedException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap_1.xml';
        $sitemap  = new Sitemap($fileName);
        $sitemap->addAlternates('http://example.com/%s/mylink4', 'es');
        $sitemap->addItem('http://example.com/es/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertTrue(file_exists($fileName));

        unlink($fileName);
    }

    /**
     * Not validated of simtemap according to XSD schema
     */
    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_a.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addAlternates('http://example.com/%s/mylink4', array('en','fr','de'));
        $sitemap->addItem('http://example.com/es/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->addItem('http://example.com/es/mylink8', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertTrue(file_exists($fileName));

        unlink($fileName);
    }

    /**
     * Not validated of simtemap according to XSD schema
     */
    public function testWritingIndex()
    {
        $fileName = __DIR__ . '/sitemap_b.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addAlternates('http://example.com/%s/mylink2', array('en','fr','de'));
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addAlternates('http://example.com/%s/mylink4', array('en','fr','de'));
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();
        $sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

        $this->assertTrue(file_exists($fileName));

        $fileNameStatic = __DIR__ . '/sitemap_static.xml';
        $staticSitemap = new Sitemap($fileNameStatic);
        $sitemap->addAlternates('http://example.com/%s/about', array('en','fr','de'));
        $staticSitemap->addItem('http://example.com/about');
        $sitemap->addAlternates('http://example.com/%s/tos', array('en','fr','de'));
        $staticSitemap->addItem('http://example.com/tos');
        $sitemap->addAlternates('http://example.com/%s/jobs', array('en','fr','de'));
        $staticSitemap->addItem('http://example.com/jobs');
        $staticSitemap->write();
        $staticSitemapUrls = $staticSitemap->getSitemapUrls('http://example.com/');

        $this->assertTrue(file_exists($fileNameStatic));

        $fileNameIndex = __DIR__ . '/sitemap_index.xml';
        $index = new Index($fileNameIndex);
        foreach ($sitemapFileUrls as $sitemapUrl) {
            $index->addSitemap($sitemapUrl);
        }
        foreach ($staticSitemapUrls as $sitemapUrl) {
            $index->addSitemap($sitemapUrl);
        }
        $index->write();

        $this->assertTrue(file_exists($fileNameIndex));

        unlink($fileName);
        unlink($fileNameStatic);
        unlink($fileNameIndex);
    }
}
