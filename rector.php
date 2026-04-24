<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/benchmarks',
        __DIR__ . '/tests',
    ])
    ->withSets([LevelSetList::UP_TO_PHP_71])
    ->withPhpVersion(PhpVersion::PHP_71)
    ->withoutParallel();
