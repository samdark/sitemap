<?php
namespace samdark\sitemap\tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use samdark\sitemap\DeflateWriter;
use samdark\sitemap\PlainFileWriter;
use samdark\sitemap\TempFileGZIPWriter;

class WriterTest extends TestCase
{
    public function testPlainFileWriterWritesDataAndIgnoresCallsAfterFinish(): void
    {
        $fileName = __DIR__ . '/plain_writer.txt';
        $writer = new PlainFileWriter($fileName);
        $writer->append('first');
        $writer->finish();
        $writer->append('second');
        $writer->finish();

        $this->assertSame('first', file_get_contents($fileName));

        unlink($fileName);
    }

    public function testPlainFileWriterRejectsDirectoryTarget(): void
    {
        $this->expectException(RuntimeException::class);

        new PlainFileWriter(__DIR__);
    }

    public function testDeflateWriterWritesDataAndIgnoresCallsAfterFinish(): void
    {
        if (!function_exists('deflate_init')) {
            $this->markTestSkipped('Incremental deflate functions are not available.');
        }

        $fileName = __DIR__ . '/deflate_writer.xml.gz';
        $writer = new DeflateWriter($fileName);
        $writer->append('<root>');
        $writer->append('</root>');
        $writer->finish();
        $writer->append('ignored');
        $writer->finish();

        $this->assertSame('<root></root>', file_get_contents('compress.zlib://' . $fileName));

        unlink($fileName);
    }

    public function testDeflateWriterRejectsDirectoryTarget(): void
    {
        if (!function_exists('deflate_init')) {
            $this->markTestSkipped('Incremental deflate functions are not available.');
        }

        $this->expectException(RuntimeException::class);

        new DeflateWriter(__DIR__);
    }

    public function testTempFileGzipWriterWritesDataAndIgnoresSecondFinish(): void
    {
        if (!extension_loaded('zlib')) {
            $this->markTestSkipped('Zlib extension is not available.');
        }

        $fileName = __DIR__ . '/temp_file_gzip_writer.xml.gz';
        $writer = new TempFileGZIPWriter($fileName);
        $writer->append('<root>');
        $writer->append('</root>');
        $writer->finish();
        $writer->finish();

        $this->assertSame('<root></root>', file_get_contents('compress.zlib://' . $fileName));

        unlink($fileName);
    }

    public function testTempFileGzipWriterRejectsDirectoryTarget(): void
    {
        if (!extension_loaded('zlib')) {
            $this->markTestSkipped('Zlib extension is not available.');
        }

        $writer = new TempFileGZIPWriter(__DIR__);

        $this->expectException(RuntimeException::class);

        $writer->finish();
    }
}
