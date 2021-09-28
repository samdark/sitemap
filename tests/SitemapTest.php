<?php
namespace samdark\sitemap\tests;

use SebastianBergmann\Timer\Timer;

use samdark\sitemap\Sitemap;

class SitemapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts validity of simtemap according to XSD schema
     * @param string $fileName
     * @param bool $xhtml
     */
    protected function assertIsValidSitemap($fileName, $xhtml = false)
    {
        $xsdFileName = $xhtml ? 'sitemap_xhtml.xsd' : 'sitemap.xsd';

        $xml = new \DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/' . $xsdFileName));
    }

    protected function assertIsOneMemberGzipFile($fileName)
    {
        $gzipMemberStartSequence = pack('H*', '1f8b08');
        $content = file_get_contents($fileName);
        $isOneMemberGzipFile = (strpos($content, $gzipMemberStartSequence, 1) === false);
        $this->assertTrue($isOneMemberGzipFile, "There are more than one gzip member in $fileName");
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


    public function testMultiLanguageSitemap()
    {
        $fileName = __DIR__ . '/sitemap_multi_language.xml';
        $sitemap = new Sitemap($fileName, true);
        $sitemap->addItem('http://example.com/mylink1');

        $sitemap->addItem(array(
            'ru' => 'http://example.com/ru/mylink2',
            'en' => 'http://example.com/en/mylink2',
        ), time());

        $sitemap->addItem(array(
            'ru' => 'http://example.com/ru/mylink3',
            'en' => 'http://example.com/en/mylink3',
        ), time(), Sitemap::HOURLY);

        $sitemap->addItem(array(
            'ru' => 'http://example.com/ru/mylink4',
            'en' => 'http://example.com/en/mylink4',
        ), time(), Sitemap::DAILY, 0.3);

        $sitemap->write();

        $this->assertTrue(file_exists($fileName));
        $this->assertIsValidSitemap($fileName, true);

        unlink($fileName);
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

    public function testMultiLanguageLocationValidation()
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);


        $sitemap->addItem(array(
            'ru' => 'http://example.com/mylink1',
            'en' => 'http://example.com/mylink2',
        ));

        $exceptionCaught = false;
        try {
            $sitemap->addItem(array(
                'ru' => 'http://example.com/mylink3',
                'en' => 'notlink',
            ), time());
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
        $sitemap->setUseGzip(true);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertTrue(file_exists($fileName));
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals('application/x-gzip', $finfo->file($fileName));
        $this->assertIsValidSitemap('compress.zlib://' . $fileName);
        $this->assertIsOneMemberGzipFile($fileName);

        unlink($fileName);
    }

    public function testMultipleFilesGzipped()
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi_gzipped.xml.gz');
        $sitemap->setUseGzip(true);
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = array(
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
        );
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists($expectedFile), "$expectedFile does not exist!");
            $this->assertEquals('application/x-gzip', $finfo->file($expectedFile));
            $this->assertIsValidSitemap('compress.zlib://' . $expectedFile);
            $this->assertIsOneMemberGzipFile($expectedFile);
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertEquals(10, count($urls), print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi_gzipped.xml.gz', $urls);
        $this->assertContains('http://example.com/sitemap_multi_gzipped_10.xml.gz', $urls);
    }

    public function testFileSizeLimit()
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi.xml');
        $sizeLimit = 1036;
        $sitemap->setMaxBytes($sizeLimit);
        $sitemap->setBufferSize(1);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = array(
            __DIR__ . '/' .'sitemap_multi.xml',
            __DIR__ . '/' .'sitemap_multi_2.xml',
            __DIR__ . '/' .'sitemap_multi_3.xml',
        );

        $this->assertEquals($sizeLimit, filesize($expectedFiles[1]));

        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists($expectedFile), "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);
            $this->assertLessThanOrEqual($sizeLimit, filesize($expectedFile), "$expectedFile exceeds the size limit");
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertEquals(3, count($urls), print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi.xml', $urls);
        $this->assertContains('http://example.com/sitemap_multi_3.xml', $urls);
    }

    public function testSmallSizeLimit()
    {
        $fileName = __DIR__ . '/sitemap_regular.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->setMaxBytes(0);
        $sitemap->setBufferSize(1);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/mylink1');
            $sitemap->write();
        } catch (\OverflowException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected OverflowException wasn\'t thrown.');
    }

    public function testBufferSizeImpact()
    {
        if (getenv('TRAVIS') == 'true') {
            $this->markTestSkipped('Can not reliably test performance on travis-ci.');
            return;
        }

        $fileName = __DIR__ . '/sitemap_big.xml';

        $times = array();

        foreach (array(1000, 10) as $bufferSize) {
            $startTime = microtime(true);

            $sitemap = new Sitemap($fileName);
            $sitemap->setBufferSize($bufferSize);
            for ($i = 0; $i < 50000; $i++) {
                $sitemap->addItem('http://example.com/mylink' . $i, time());
            }
            $sitemap->write();

            $times[] = microtime(true) - $startTime;
            unlink($fileName);
        }

        $this->assertLessThan($times[0] * 1.2, $times[1]);
    }
}
