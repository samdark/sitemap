<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Sitemap;
use samdark\sitemap\Url;

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $fileName
     */
    protected static function assertIsValidSitemap($fileName)
    {
        $xml = new \DOMDocument();
        $xml->load($fileName);
        static::assertTrue($xml->schemaValidate(__DIR__ . '/sitemap.xsd'));
    }

    public function testWritingFile()
    {
        $fileName = __DIR__ . '/sitemap_regular.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem(new Url('http://example.com/mylink1'));
        $sitemap->addItem(
            (new Url('http://example.com/mylink2'))
                ->setLastModified(time())
        );
        $sitemap->addItem(
            (new Url('http://example.com/mylink3'))
                ->setLastModified(time())
                ->setChangeFrequency(Url::HOURLY)
        );
        $sitemap->addItem(
            (new Url('http://example.com/mylink4'))
                ->setLastModified(time())
                ->setChangeFrequency(Url::DAILY)
                ->setPriority(0.3)
        );
        $sitemap->addItem(
            (new Url('http://example.com/mylink5', time(), Url::DAILY, 0.3))
        );
        $sitemap->write();

        static::assertFileExists($fileName);
        static::assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    public function testMultipleFiles()
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi.xml');
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem(
                (new Url('http://example.com/mylink' . $i))
                    ->setLastModified(time())
            );
        }
        $sitemap->write();

        $expectedFiles = [
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
        ];
        foreach ($expectedFiles as $expectedFile) {
            static::assertFileExists($expectedFile);
            static::assertIsValidSitemap($expectedFile);
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        static::assertCount(10, $urls);
        static::assertContains('http://example.com/sitemap_multi.xml', $urls);
        static::assertContains('http://example.com/sitemap_multi_10.xml', $urls);
    }
}
