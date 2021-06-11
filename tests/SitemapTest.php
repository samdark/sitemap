<?php

namespace SamDark\Sitemap\tests;

use InvalidArgumentException;
use OverflowException;
use SamDark\Sitemap\Frequency;
use SamDark\Sitemap\Extension\AlternateLink;
use SamDark\Sitemap\Url;

use SamDark\Sitemap\Sitemap;

class SitemapTest extends TestCase
{
    /**
     * Asserts that gzip is a single member one (multi-member gzip can't be read by bots reliably)
     * @param string $fileName
     */
    protected function assertIsOneMemberGzipFile(string $fileName)
    {
        $gzipMemberStartSequence = pack('H*', '1f8b08');
        $content = file_get_contents($fileName);
        $isOneMemberGzipFile = (strpos($content, $gzipMemberStartSequence, 1) === false);
        $this->assertTrue($isOneMemberGzipFile, "There are more than one gzip member in $fileName");
    }

    public function testWritingFile()
    {
        $fileName = $this->getTempPath('testWritingFile.xml');

        $sitemap = new Sitemap($fileName);
        $sitemap->addUrl(new Url('http://example.com/mylink1'));
        $sitemap->addUrl(
            (new Url('http://example.com/mylink2'))
                ->setLastModified(new \DateTime())
        );
        $sitemap->addUrl(
            (new Url('http://example.com/mylink3'))
                ->setLastModified(new \DateTime())
                ->setChangeFrequency(Frequency::HOURLY)
        );
        $sitemap->addUrl(
            (new Url('http://example.com/mylink4'))
                ->setChangeFrequency(Frequency::DAILY)
                ->setLastModified(new \DateTime())
                ->setPriority(0.3)
        );
        $sitemap->write();

        $this->assertFileExists($fileName);
        $this->assertValidXml($fileName, 'sitemap');
    }

    public function testMultipleFiles()
    {
        $sitemap = new Sitemap($this->getTempPath('/testMultipleFiles.xml'));
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addUrl(
                (new Url('http://example.com/mylink' . $i))
                    ->setLastModified(new \DateTime())
            );
        }
        $sitemap->write();

        $expectedFiles = [
            $this->getTempPath('testMultipleFiles.xml'),
            $this->getTempPath('testMultipleFiles-2.xml'),
            $this->getTempPath('testMultipleFiles-3.xml'),
            $this->getTempPath('testMultipleFiles-4.xml'),
            $this->getTempPath('testMultipleFiles-5.xml'),
            $this->getTempPath('testMultipleFiles-6.xml'),
            $this->getTempPath('testMultipleFiles-7.xml'),
            $this->getTempPath('testMultipleFiles-8.xml'),
            $this->getTempPath('testMultipleFiles-9.xml'),
            $this->getTempPath('testMultipleFiles-10.xml'),
        ];
        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertValidXml($expectedFile, 'sitemap');
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(10, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/testMultipleFiles.xml', $urls);
        $this->assertContains('http://example.com/testMultipleFiles-10.xml', $urls);
    }


    public function testMultiLanguageSitemap()
    {
        $fileName = $this->getTempPath('testMultiLanguageSitemap.xml');
        $sitemap = new Sitemap($fileName, [AlternateLink::class]);
        $sitemap->addUrl(
            (new Url('http://example.com/en/mylink2'))
                ->setLastModified(new \DateTime())
                ->setChangeFrequency(Frequency::HOURLY)
                ->add(new AlternateLink('en', 'http://example.com/en/mylink2'))
                ->add(new AlternateLink('ru', 'http://example.com/ru/mylink2'))
        );

        $sitemap->write();

        $this->assertFileExists($fileName);
        $this->assertValidXml($fileName, 'sitemap_xhtml');
    }


    public function testFrequencyValidation()
    {
        $this->expectException(InvalidArgumentException::class);

        $fileName = $this->getTempPath('testFrequencyValidation.xml');
        $sitemap = new Sitemap($fileName);
        $sitemap->addUrl(
            (new Url('http://example.com/mylink2'))
                ->setChangeFrequency('invalid')
        );
    }

    public function testPriorityValidation()
    {
        $fileName = $this->getTempPath('testPriorityValidation.xml');
        $sitemap = new Sitemap($fileName);

        $this->expectException(InvalidArgumentException::class);

        $sitemap->addUrl(
            (new Url('http://example.com/mylink1'))
                ->setPriority(2.0)
        );
    }

    public function testWritingFileGzipped()
    {
        $fileName = $this->getTempPath('testWritingFileGzipped.xml.gz');
        $sitemap = new Sitemap($fileName);
        $sitemap->setUseGzip(true);
        $sitemap->addUrl(new Url('http://example.com/mylink1'));
        $sitemap->write();

        $this->assertFileExists($fileName);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->assertRegExp('!application/(x-)?gzip!', $finfo->file($fileName));
        $this->assertValidXml('compress.zlib://' . $fileName, 'sitemap');
        $this->assertIsOneMemberGzipFile($fileName);
    }

    public function testMultipleFilesGzipped()
    {
        $sitemap = new Sitemap($this->getTempPath('testMultipleFilesGzipped.xml.gz'));
        $sitemap->setUseGzip(true);
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addUrl(
                (new Url('http://example.com/mylink' . $i))
                    ->setLastModified(new \DateTime())
            );
        }
        $sitemap->write();

        $expectedFiles = [
            $this->getTempPath('testMultipleFilesGzipped.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-2.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-3.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-4.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-5.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-6.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-7.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-8.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-9.xml.gz'),
            $this->getTempPath('testMultipleFilesGzipped-10.xml.gz'),
        ];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($expectedFiles as $expectedFile) {

            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertRegExp('!application/(x-)?gzip!', $finfo->file($expectedFile));
            $this->assertValidXml('compress.zlib://' . $expectedFile, 'sitemap');
            $this->assertIsOneMemberGzipFile($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(10, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/testMultipleFilesGzipped.xml.gz', $urls);
        $this->assertContains('http://example.com/testMultipleFilesGzipped-10.xml.gz', $urls);
    }

    public function testFileSizeLimit()
    {
        $sitemap = new Sitemap($this->getTempPath('testFileSizeLimit.xml'));
        $sizeLimit = 1036;
        $sitemap->setMaxBytes($sizeLimit);
        $sitemap->setBufferSize(1);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addUrl(
                (new Url('http://example.com/mylink' . $i))
                    ->setLastModified(new \DateTime())
            );
        }
        $sitemap->write();

        $expectedFiles = [
            $this->getTempPath('testFileSizeLimit.xml'),
            $this->getTempPath('testFileSizeLimit-2.xml'),
            $this->getTempPath('testFileSizeLimit-3.xml'),
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);
            $this->assertValidXml($expectedFile, 'sitemap');
            $this->assertLessThanOrEqual($sizeLimit, filesize($expectedFile), "$expectedFile exceeds the size limit");
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(3, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/testFileSizeLimit.xml', $urls);
        $this->assertContains('http://example.com/testFileSizeLimit-3.xml', $urls);
    }

    public function testSmallSizeLimit()
    {
        $this->expectException(OverflowException::class);

        $fileName = $this->getTempPath('testSmallSizeLimit.xml');
        $sitemap = new Sitemap($fileName);
        $sitemap->setMaxBytes(0);
        $sitemap->setBufferSize(1);
        $sitemap->addUrl(new Url('http://example.com/mylink1'));
        $sitemap->write();
    }

    public function testBufferSizeImpact()
    {
        if (getenv('TRAVIS') === 'true') {
            $this->markTestSkipped('Can not reliably test performance on travis-ci.');
            return;
        }

        $fileName = $this->getTempPath('testBufferSizeImpact.xml');

        $times = [];

        foreach ([1000, 10] as $bufferSize) {
            $startTime = microtime(true);

            $sitemap = new Sitemap($fileName);
            $sitemap->setBufferSize($bufferSize);
            for ($i = 0; $i < 50000; $i++) {
                $sitemap->addUrl(
                    (new Url('http://example.com/mylink' . $i))
                        ->setLastModified(new \DateTime())
                );
            }
            $sitemap->write();

            $times[] = microtime(true) - $startTime;
        }

        $this->assertLessThan($times[0] * 1.2, $times[1]);
    }
}
