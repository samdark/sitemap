<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/DeflateWriter.php',
        __DIR__ . '/Index.php',
        __DIR__ . '/PlainFileWriter.php',
        __DIR__ . '/Sitemap.php',
        __DIR__ . '/TempFileGZIPWriter.php',
        __DIR__ . '/UrlEncoderTrait.php',
        __DIR__ . '/WriterInterface.php',
        __DIR__ . '/benchmarks',
        __DIR__ . '/tests',
    ])
    ->withSets([LevelSetList::UP_TO_PHP_70])
    ->withPhpVersion(PhpVersion::PHP_70)
    ->withoutParallel();
