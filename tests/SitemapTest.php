<?php
namespace samdark\sitemap\tests;

use DOMDocument;
use finfo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use RuntimeException;
use samdark\sitemap\Image;
use samdark\sitemap\Sitemap;

class SitemapTest extends TestCase
{
    private const HEADER_LENGTH = 100;
    private const FOOTER_LENGTH = 10;
    private const ELEMENT_LENGTH_WITHOUT_URL = 137;

    /**
     * Asserts validity of sitemap according to the XSD schema.
     * @param string $fileName File name.
     * @param bool $xhtml Whether XHTML schema should be used.
     */
    protected function assertIsValidSitemap(string $fileName, bool $xhtml = false): void
    {
        $xsdFileName = $xhtml ? 'sitemap_xhtml.xsd' : 'sitemap_xml.xsd';

        $xml = new DOMDocument();
        $xml->load($fileName);
        $this->assertTrue($xml->schemaValidate(__DIR__ . '/' . $xsdFileName));
    }

    protected function assertIsOneMemberGzipFile(string $fileName): void
    {
        $gzipMemberStartSequence = pack('H*', '1f8b08');
        $content = file_get_contents($fileName);
        $isOneMemberGzipFile = (strpos($content, $gzipMemberStartSequence, 1) === false);
        $this->assertTrue($isOneMemberGzipFile, "There are more than one gzip member in $fileName");
    }

    public function testWritingFile(): void
    {
        $fileName = __DIR__ . '/sitemap_regular.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName);
        $this->assertFileExists($fileName);

        unlink($fileName);

        $this->assertFileDoesNotExist($fileName);
    }


    public function testAgainstExpectedXml(): void
    {
        $fileName = __DIR__ . '/sitemap_regular.xml';
        $sitemap = new Sitemap($fileName);

        $images = [
            new Image('https://example.com/picture1.jpg', 'The caption', 'Vienna, Austria', 'The title', 'https://example.com/images.txt'),
            new Image('https://example.com/picture2.jpg')
        ];
        $sitemap->addItem('http://example.com/test.html&q=name', (new \DateTime('2021-01-11 01:01'))->format('U'), null, null, $images);
        $sitemap->addItem('http://example.com/mylink?foo=bar', (new \DateTime('2021-01-02 03:04'))->format('U'), Sitemap::HOURLY);

        $sitemap->addItem('http://example.com/mylink4', (new \DateTime('2021-01-02 03:04'))->format('U'), Sitemap::DAILY, 0.3);

        $sitemap->write();

        $this->assertFileExists($fileName);

        $expected = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
 <url>
  <loc>http://example.com/test.html&amp;q=name</loc>
  <lastmod>2021-01-11T01:01:00+00:00</lastmod>
  <image:image>
   <image:loc>https://example.com/picture1.jpg</image:loc>
   <image:caption>The caption</image:caption>
   <image:geo_location>Vienna, Austria</image:geo_location>
   <image:title>The title</image:title>
   <image:license>https://example.com/images.txt</image:license>
  </image:image>
  <image:image>
   <image:loc>https://example.com/picture2.jpg</image:loc>
  </image:image>
 </url>
 <url>
  <loc>http://example.com/mylink?foo=bar</loc>
  <lastmod>2021-01-02T03:04:00+00:00</lastmod>
  <changefreq>hourly</changefreq>
 </url>
 <url>
  <loc>http://example.com/mylink4</loc>
  <lastmod>2021-01-02T03:04:00+00:00</lastmod>
  <changefreq>daily</changefreq>
  <priority>0.3</priority>
 </url>
</urlset>
EOF;

        $x = trim(file_get_contents($fileName));

        $this->assertEquals($expected, $x);
    }

    public function testMultipleFiles(): void
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi.xml');
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
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
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);
            unlink($expectedFile);
        }

        $this->assertEquals($expectedFiles, $sitemap->getWrittenFilePath());

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(10, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi.xml', $urls);
        $this->assertContains('http://example.com/sitemap_multi_10.xml', $urls);
    }

    public function testMultiLanguageSitemapWithImages(): void
    {
        $fileName = __DIR__ . '/sitemap_multi_language.xml';
        $sitemap = new Sitemap($fileName, true);

        $images = [
            new Image('https://example.com/picture1.jpg'), new Image('https://example.com/picture2.jpg')
        ];
        $sitemap->addItem('http://example.com/mylink1', null, null, null, $images);

        $sitemap->addItem([
            'ru' => 'http://example.com/ru/mylink2',
            'en' => 'http://example.com/en/mylink2',
        ], time());

        $sitemap->addItem([
            'ru' => 'http://example.com/ru/mylink3',
            'en' => 'http://example.com/en/mylink3',
        ], time(), Sitemap::HOURLY);

        $sitemap->addItem([
            'ru' => 'http://example.com/ru/mylink4',
            'en' => 'http://example.com/en/mylink4',
        ], time(), Sitemap::DAILY, 0.3);

        $sitemap->write();

        $this->assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName, true);

        unlink($fileName);
    }

    public function testMultiLanguageSitemapFileSplitting(): void
    {
        // Each multi-language addItem() with 2 languages writes 2 <url> elements.
        // With maxUrls = 2, the second addItem() (adding 2 more URLs) should trigger a new file.
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multilang_split.xml', true);
        $sitemap->setMaxUrls(2);

        $sitemap->addItem([
            'ru' => 'http://example.com/ru/mylink1',
            'en' => 'http://example.com/en/mylink1',
        ]);

        $sitemap->addItem([
            'ru' => 'http://example.com/ru/mylink2',
            'en' => 'http://example.com/en/mylink2',
        ]);

        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/sitemap_multilang_split.xml',
            __DIR__ . '/sitemap_multilang_split_2.xml',
        ];

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile, true);
            unlink($expectedFile);
        }
    }


    public function testFrequencyValidation(): void
    {
        $this->expectException('InvalidArgumentException');

        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time(), 'invalid');

        unlink($fileName);
    }

    public function testInvalidDirectoryValidation(): void
    {
        $this->expectException('InvalidArgumentException');

        new Sitemap(__DIR__ . '/missing-directory/sitemap.xml');
    }

    public function testExistingUnwritableFileValidation(): void
    {
        $fileName = __DIR__ . '/sitemap_unwritable.xml';
        file_put_contents($fileName, 'previous sitemap contents');
        chmod($fileName, 0444);

        if (is_writable($fileName)) {
            chmod($fileName, 0644);
            unlink($fileName);
            $this->markTestSkipped('Filesystem does not make the file unwritable with chmod(0444).');
        }

        $exceptionCaught = false;
        try {
            $sitemap = new Sitemap($fileName);
            $sitemap->addItem('http://example.com/mylink1');
        } catch (RuntimeException $e) {
            $exceptionCaught = true;
        } finally {
            if (file_exists($fileName)) {
                chmod($fileName, 0644);
                unlink($fileName);
            }
        }

        $this->assertTrue($exceptionCaught, 'Expected RuntimeException wasn\'t thrown.');
    }

    public function testPriorityValidation(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/mylink1');
            $sitemap->addItem('http://example.com/mylink2', time(), 'always', 2.0);
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testLocationValidation(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/mylink1');
            $sitemap->addItem('notlink', time());
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testLocationValidationRejectsUrlsWithSpaces(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);

        $exceptionCaught = false;
        try {
            $sitemap->addItem('http://example.com/valid');
            $sitemap->addItem('http://bad host/invalid');
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testLocationValidationRejectsInvalidHostsAndPorts(): void
    {
        $locations = [
            'http://example..com/path',
            'http://example-.com/path',
            'http://example.com:99999/path',
            'http://' . str_repeat('a.', 126) . 'com/path',
        ];

        foreach ($locations as $i => $location) {
            $fileName = __DIR__ . "/sitemap_invalid_ascii_{$i}.xml";
            $sitemap = new Sitemap($fileName);

            try {
                $sitemap->addItem($location);
                $this->fail("Expected InvalidArgumentException for {$location}.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($location, $e->getMessage());
            } finally {
                unset($sitemap);
                if (file_exists($fileName)) {
                    unlink($fileName);
                }
            }
        }
    }

    public function testNonHttpAsciiLocationIsAccepted(): void
    {
        $fileName = __DIR__ . '/sitemap_ftp.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('ftp://example.com/files/sitemap-export.xml');
        $sitemap->write();

        $this->assertFileExists($fileName);
        $this->assertStringContainsString('ftp://example.com/files/sitemap-export.xml', file_get_contents($fileName));
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    public function testMultiLanguageLocationValidation(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);


        $sitemap->addItem([
            'ru' => 'http://example.com/mylink1',
            'en' => 'http://example.com/mylink2',
        ]);

        $exceptionCaught = false;
        try {
            $sitemap->addItem([
                'ru' => 'http://example.com/mylink3',
                'en' => 'notlink',
            ], time());
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testMultiLanguageFrequencyValidation(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName, true);

        $exceptionCaught = false;
        try {
            $sitemap->addItem([
                'de' => 'http://example.com/de/mylink1',
                'en' => 'http://example.com/en/mylink1',
            ], time(), 'invalid');
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unset($sitemap);
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testMultiLanguagePriorityValidation(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName, true);

        $exceptionCaught = false;
        try {
            $sitemap->addItem([
                'de' => 'http://example.com/de/mylink1',
                'en' => 'http://example.com/en/mylink1',
            ], time(), Sitemap::DAILY, 2.0);
        } catch (InvalidArgumentException $e) {
            $exceptionCaught = true;
        }

        unset($sitemap);
        if (file_exists($fileName)) {
            unlink($fileName);
        }

        $this->assertTrue($exceptionCaught, 'Expected InvalidArgumentException wasn\'t thrown.');
    }

    public function testWritingFileGzipped(): void
    {
        $fileName = __DIR__ . '/sitemap_gzipped.xml.gz';
        $sitemap = new Sitemap($fileName);
        $sitemap->setUseGzip(true);
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->write();

        $this->assertFileExists($fileName);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->assertMatchesRegularExpression('!application/(x-)?gzip!', $finfo->file($fileName));
        $this->assertIsValidSitemap('compress.zlib://' . $fileName);
        $this->assertIsOneMemberGzipFile($fileName);

        unlink($fileName);
    }

    public function testMultipleFilesGzipped(): void
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi_gzipped.xml.gz');
        $sitemap->setUseGzip(true);
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
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertMatchesRegularExpression('!application/(x-)?gzip!', $finfo->file($expectedFile));
            $this->assertIsValidSitemap('compress.zlib://' . $expectedFile);
            $this->assertIsOneMemberGzipFile($expectedFile);
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(10, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi_gzipped.xml.gz', $urls);
        $this->assertContains('http://example.com/sitemap_multi_gzipped_10.xml.gz', $urls);
    }

    public function testFileSizeLimit(): void
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_multi.xml');
        $sizeLimit = 1036;
        $sitemap->setMaxBytes($sizeLimit);
        $sitemap->setBufferSize(1);

        for ($i = 0; $i < 20; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/' .'sitemap_multi.xml',
            __DIR__ . '/' .'sitemap_multi_2.xml',
            __DIR__ . '/' .'sitemap_multi_3.xml',
        ];

        $this->assertEquals($sizeLimit, filesize($expectedFiles[1]));

        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);
            $this->assertLessThanOrEqual($sizeLimit, filesize($expectedFile), "$expectedFile exceeds the size limit");
            unlink($expectedFile);
        }

        $urls = $sitemap->getSitemapUrls('http://example.com/');
        $this->assertCount(3, $urls, print_r($urls, true));
        $this->assertContains('http://example.com/sitemap_multi.xml', $urls);
        $this->assertContains('http://example.com/sitemap_multi_3.xml', $urls);
    }

    public function testSmallSizeLimit(): void
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

    public function testWritingFileWithoutIndent(): void
    {
        $fileName = __DIR__ . '/sitemap_no_indent.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->setUseIndent(false);
        $sitemap->addItem('http://example.com/mylink1', 100, Sitemap::DAILY, 0.5);
        $sitemap->write();

        $this->assertFileExists($fileName);
        $content = trim(file_get_contents($fileName));
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
            . '<url><loc>http://example.com/mylink1</loc>'
            . '<lastmod>1970-01-01T00:01:40+00:00</lastmod>'
            . '<changefreq>daily</changefreq>'
            . '<priority>0.5</priority></url></urlset>';

        $this->assertSame($expected, $content);
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    public function testChangingGzipAfterWritingItemsIsRejected(): void
    {
        $fileName = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://example.com/mylink1');

        $exceptionCaught = false;
        try {
            $sitemap->setUseGzip(true);
        } catch (RuntimeException $e) {
            $exceptionCaught = true;
        }

        unset($sitemap);
        unlink($fileName);

        $this->assertTrue($exceptionCaught, 'Expected RuntimeException wasn\'t thrown.');
    }

    public function testBufferSizeDoesNotChangeGeneratedSitemap(): void
    {
        $contents = [];

        foreach ([1000, 10] as $bufferSize) {
            $fileName = __DIR__ . "/sitemap_buffer_size_{$bufferSize}.xml";
            $sitemap = new Sitemap($fileName);
            $sitemap->setBufferSize($bufferSize);
            for ($i = 0; $i < 20; $i++) {
                $sitemap->addItem('http://example.com/mylink' . $i, 100);
            }
            $sitemap->write();

            $this->assertFileExists($fileName);
            $this->assertIsValidSitemap($fileName);
            $contents[$bufferSize] = file_get_contents($fileName);

            unlink($fileName);
        }

        $this->assertSame($contents[1000], $contents[10]);
    }

    public function testBufferSizeIsNotTooBigOnFinishFileInWrite(): void
    {
        $time = 100;
        $urlLength = 13;
        $urlsQty = 4;

        $sitemapPath = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($sitemapPath);
        $sitemap->setBufferSize(3);
        $sitemap->setMaxUrls(4);
        $sitemap->setMaxBytes(
            self::HEADER_LENGTH + self::FOOTER_LENGTH + self::ELEMENT_LENGTH_WITHOUT_URL * $urlsQty
                + $urlLength * $urlsQty - 1
        );

        for ($i = 0; $i < $urlsQty; $i++) {
            $sitemap->addItem(
                // URL is 13 bytes.
                "https://a.b/{$i}",
                $time,
                Sitemap::WEEKLY,
                1
            );
        }
        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/sitemap.xml',
            __DIR__ . '/sitemap_2.xml',
        ];
        $expected[] = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
 <url>
  <loc>https://a.b/0</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
 <url>
  <loc>https://a.b/1</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
 <url>
  <loc>https://a.b/2</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
</urlset>
EOF;
        $expected[] = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
 <url>
  <loc>https://a.b/3</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
</urlset>
EOF;
        foreach ($expectedFiles as $expectedFileNumber => $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);

            $actual = trim(file_get_contents($expectedFile));
            $this->assertEquals($expected[$expectedFileNumber], $actual);

            unlink($expectedFile);
        }
    }

    public function testBufferSizeIsNotTooBigOnFinishFileInAddItem(): void
    {
        $time = 100;
        $urlLength = 13;
        $urlsQty = 5;

        $sitemapPath = __DIR__ . '/sitemap.xml';
        $sitemap = new Sitemap($sitemapPath);
        $sitemap->setBufferSize(3);
        $sitemap->setMaxUrls(4);
        $sitemap->setMaxBytes(
            // Formula: 100 + 10 + 137 * 4.
            self::HEADER_LENGTH + self::FOOTER_LENGTH + self::ELEMENT_LENGTH_WITHOUT_URL * 4
                + $urlLength * 4 - 1
        );

        for ($i = 0; $i < $urlsQty; $i++) {
            $sitemap->addItem(
                // URL is 13 bytes.
                "https://a.b/{$i}",
                $time,
                Sitemap::WEEKLY,
                1
            );
        }
        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/sitemap.xml',
            __DIR__ . '/sitemap_2.xml',
        ];
        $expected[] = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
 <url>
  <loc>https://a.b/0</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
 <url>
  <loc>https://a.b/1</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
 <url>
  <loc>https://a.b/2</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
</urlset>
EOF;
        $expected[] = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
 <url>
  <loc>https://a.b/3</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
 <url>
  <loc>https://a.b/4</loc>
  <lastmod>1970-01-01T00:01:40+00:00</lastmod>
  <changefreq>weekly</changefreq>
  <priority>1.0</priority>
 </url>
</urlset>
EOF;
        foreach ($expectedFiles as $expectedFileNumber => $expectedFile) {
            $this->assertFileExists($expectedFile, "$expectedFile does not exist!");
            $this->assertIsValidSitemap($expectedFile);

            $actual = trim(file_get_contents($expectedFile));
            $this->assertEquals($expected[$expectedFileNumber], $actual);

            unlink($expectedFile);
        }
    }

    public function testGetCurrentFilePathIsOverridable(): void
    {
        $customSitemap = new class(__DIR__ . '/sitemap_custom.xml') extends Sitemap {
            protected function buildCurrentFilePath(string $filePath, int $fileCount): string
            {
                if ($fileCount < 2) {
                    return $filePath;
                }
                $parts = pathinfo($filePath);
                return $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename'] . '-' . $fileCount . '.' . $parts['extension'];
            }
        };
        $customSitemap->setMaxUrls(2);

        for ($i = 0; $i < 4; $i++) {
            $customSitemap->addItem('http://example.com/mylink' . $i);
        }
        $customSitemap->write();

        $expectedFiles = [
            __DIR__ . '/sitemap_custom.xml',
            __DIR__ . '/sitemap_custom-2.xml',
        ];
        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);
            $this->assertIsValidSitemap($expectedFile);
            unlink($expectedFile);
        }
    }

    public function testStylesheetIsIncludedInOutput(): void
    {
        $fileName = __DIR__ . '/sitemap_stylesheet.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->setStylesheet('http://example.com/sitemap.xsl');
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->write();

        $this->assertFileExists($fileName);
        $content = file_get_contents($fileName);
        $this->assertStringContainsString('<?xml-stylesheet', $content);
        $this->assertStringContainsString('type="text/xsl"', $content);
        $this->assertStringContainsString('href="http://example.com/sitemap.xsl"', $content);
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    public function testStylesheetInvalidUrlThrowsException(): void
    {
        $this->expectException('InvalidArgumentException');

        $sitemap = new Sitemap(__DIR__ . '/sitemap.xml');
        $sitemap->setStylesheet('not-a-valid-url');
    }

    public function testStylesheetInMultipleFiles(): void
    {
        $sitemap = new Sitemap(__DIR__ . '/sitemap_stylesheet_multi.xml');
        $sitemap->setStylesheet('http://example.com/sitemap.xsl');
        $sitemap->setMaxUrls(2);

        for ($i = 0; $i < 4; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i, time());
        }
        $sitemap->write();

        $expectedFiles = [
            __DIR__ . '/sitemap_stylesheet_multi.xml',
            __DIR__ . '/sitemap_stylesheet_multi_2.xml',
        ];
        foreach ($expectedFiles as $expectedFile) {
            $this->assertFileExists($expectedFile);
            $content = file_get_contents($expectedFile);
            $this->assertStringContainsString('<?xml-stylesheet', $content);
            $this->assertStringContainsString('type="text/xsl"', $content);
            $this->assertStringContainsString('href="http://example.com/sitemap.xsl"', $content);
            $this->assertIsValidSitemap($expectedFile);
            unlink($expectedFile);
        }
    }

    public function testFileEndsWithClosingTagWhenWriteNotCalledExplicitly(): void
    {
        $fileName = __DIR__ . '/sitemap_no_explicit_write.xml';
        $sitemap = new Sitemap($fileName);

        // Add enough items to exceed the default buffer size so data is flushed to disk.
        for ($i = 1; $i <= 10; $i++) {
            $sitemap->addItem('http://example.com/mylink' . $i);
        }

        // Destroy the sitemap object without calling write(), simulating a forgotten write().
        unset($sitemap);

        $this->assertFileExists($fileName);

        $content = trim(file_get_contents($fileName));

        // The file must end with the closing urlset tag even though write() was not called explicitly.
        $this->assertStringEndsWith('</urlset>', $content, 'Sitemap file must end with </urlset> even when write() is not called explicitly.');

        unlink($fileName);
    }

    public function testInternationalUrlEncoding(): void
    {
        $fileName = __DIR__ . '/sitemap_international.xml';
        $sitemap = new Sitemap($fileName);

        // Test with Arabic characters in URL path.
        $sitemap->addItem('http://example.com/ar/العامل-الماهر-كاريكاتير');

        // Test with Chinese characters.
        $sitemap->addItem('http://example.com/zh/测试页面');

        // Test with already encoded URL, which should not double-encode.
        $sitemap->addItem('http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84');

        // Test with query string containing non-ASCII.
        $sitemap->addItem('http://example.com/search?q=café');

        $sitemap->write();

        $this->assertFileExists($fileName);

        $content = file_get_contents($fileName);

        // Arabic text should be percent-encoded.
        $this->assertStringContainsString('http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84-%D8%A7%D9%84%D9%85%D8%A7%D9%87%D8%B1-%D9%83%D8%A7%D8%B1%D9%8A%D9%83%D8%A7%D8%AA%D9%8A%D8%B1', $content);

        // Chinese text should be percent-encoded.
        $this->assertStringContainsString('http://example.com/zh/%E6%B5%8B%E8%AF%95%E9%A1%B5%E9%9D%A2', $content);

        // Already encoded URL should remain the same without double-encoding.
        $this->assertStringContainsString('http://example.com/ar/%D8%A7%D9%84%D8%B9%D8%A7%D9%85%D9%84', $content);

        // Query string should be encoded.
        $this->assertStringContainsString('http://example.com/search?q=caf%C3%A9', $content);

        $this->assertIsValidSitemap($fileName);
        unlink($fileName);
    }

    public function testComplexApplicationUrlEncoding(): void
    {
        $fileName = __DIR__ . '/sitemap_complex_url.xml';
        $sitemap = new Sitemap($fileName);
        $sitemap->addItem('http://user:secret@example.com:8080/search/кафе?tag=новости&preview#главная');
        $sitemap->write();

        $this->assertFileExists($fileName);
        $content = file_get_contents($fileName);
        $this->assertStringContainsString(
            'http://user:secret@example.com:8080/search/%D0%BA%D0%B0%D1%84%D0%B5?tag=%D0%BD%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8&amp;preview#%D0%B3%D0%BB%D0%B0%D0%B2%D0%BD%D0%B0%D1%8F',
            $content
        );

        $this->assertIsValidSitemap($fileName);
        unlink($fileName);
    }
}
