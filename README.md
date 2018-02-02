Sitemap
=======

Sitemap and sitemap index builder.

<img src="https://travis-ci.org/samdark/sitemap.svg" />

Features
--------

- Create sitemap files: either regular or gzipped.
- Create multi-language sitemap files.
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
use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;

// create sitemap
$sitemap = new Sitemap(__DIR__ . '/sitemap.xml');

// add some URLs
$sitemap->addItem('http://example.com/mylink1');
$sitemap->addItem('http://example.com/mylink2', time());
$sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
$sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);

// write it
$sitemap->write();

// get URLs of sitemaps written
$sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

// create sitemap for static files
$staticSitemap = new Sitemap(__DIR__ . '/sitemap_static.xml');

// add some URLs
$staticSitemap->addItem('http://example.com/about');
$staticSitemap->addItem('http://example.com/tos');
$staticSitemap->addItem('http://example.com/jobs');

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
use samdark\sitemap\Sitemap;

// create sitemap
// be sure to pass `true` as second parameter to specify XHTML namespace
$sitemap = new Sitemap(__DIR__ . '/sitemap_multi_language.xml', true);

// Set URL limit to fit in default limit of 50000 (default limit / number of languages) 
$sitemap->setMaxUrls(25000);

// add some URLs
$sitemap->addItem('http://example.com/mylink1');

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
