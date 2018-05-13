Sitemap
=======

Sitemap and sitemap index builder.

<img src="https://travis-ci.org/samdark/sitemap.svg" />

Features
--------

- Create sitemap files: either regular or gzipped.
- Sitemap extensions support. Included extensions are multi-language sitemaps, video sitemaps, image siteamaps. 
- Create sitemap index files.
- Automatically creates new file if either URL limit or file size limit is reached.
- Fast and memory efficient.

Installation
------------

Installation via Composer is very simple:

```
composer require samdark/sitemap
```

After that, make sure your application autoloads Composer classes by including
`vendor/autoload.php`.

How to use it
-------------

```php
use SamDark\Sitemap\Sitemap;
use SamDark\Sitemap\Index;

// create sitemap
$sitemap = new Sitemap(__DIR__ . '/sitemap.xml');

// add some URLs
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

// write it
$sitemap->write();

// get URLs of sitemaps written
$sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

// create sitemap for static files
$staticSitemap = new Sitemap(__DIR__ . '/sitemap_static.xml');

// add some URLs
$staticSitemap->addUrl(new Url('http://example.com/about'));
$staticSitemap->addUrl(new Url('http://example.com/tos'));
$staticSitemap->addUrl(new Url('http://example.com/jobs'));

// write it
$staticSitemap->write();

// get URLs of sitemaps written
$staticSitemapUrls = $staticSitemap->getSitemapUrls('http://example.com/');

// create sitemap index file
$index = new Index(__DIR__ . '/sitemap_index.xml');

// add URLs
foreach ($sitemapFileUrls as $sitemapUrl) {
    $index->addSitemap($sitemapUrl);
}

// add more URLs
foreach ($staticSitemapUrls as $sitemapUrl) {
    $index->addSitemap($sitemapUrl);
}

// write it
$index->write();
```

Multi-language sitemap
----------------------

```php
use SamDark\Sitemap\Sitemap;

// create sitemap declaring you need alternate links support
$sitemap = new Sitemap(__DIR__ . '/sitemap_multi_language.xml', [AlternateLink::class]);

// add some URLs

$sitemap->addUrl(
    (new Url('http://example.com/en/mylink2'))
        ->setLastModified(new \DateTime())
        ->setChangeFrequency(Frequency::HOURLY)
        ->add(new AlternateLink('en', 'http://example.com/en/mylink1'))
        ->add(new AlternateLink('ru', 'http://example.com/ru/mylink1'))
);

$sitemap->addUrl(
    (new Url('http://example.com/en/mylink2'))
        ->setLastModified(new \DateTime())
        ->setChangeFrequency(Frequency::HOURLY)
        ->add(new AlternateLink('en', 'http://example.com/en/mylink2'))
        ->add(new AlternateLink('ru', 'http://example.com/ru/mylink2'))
);

// write it
$sitemap->write();
```

Options
-------

There are methods to configure `Sitemap` instance:
 
- `setMaxUrls($number)`. Sets maximum number of URLs to write in a single file.
  Default is 50000 which is the limit according to specification and most of
  existing implementations.
- `setMaxBytes($number)`. Sets maximum size of a single site map file.
  Default is 10MiB which should be compatible with most current search engines.
- `setBufferSize($number)`. Sets number of URLs to be kept in memory before writing it to file.
  Default is 10. Bigger values give marginal benefits.
  On the other hand when the file size limit is hit, the complete buffer must be written to the next file.
- `setUseIndent($bool)`. Sets if XML should be indented. Default is true.
- `setUseGzip($bool)`. Sets whether the resulting sitemap files will be gzipped or not.
  Default is `false`. `zlib` extension must be enabled to use this feature.

There is a method to configure `Index` instance:

- `setUseGzip($bool)`. Sets whether the resulting index file will be gzipped or not.
  Default is `false`. `zlib` extension must be enabled to use this feature.

Running tests
-------------

In order to run tests perform the following commands:

```
composer install
./vendor/bin/phpunit
```
