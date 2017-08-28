Sitemap
=======

Sitemap and sitemap index builder.

<img src="https://travis-ci.org/samdark/sitemap.svg" />

Features
--------

- Create sitemap files.
- Create sitemap files with *hreflang* alternates.
- Create sitemap index files.
- Automatically creates new file if 50000 URLs limit is reached.
- Memory efficient buffer of configurable size.

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
// add alternates
$sitemap->addAlternates('http://example.com/%s/mylink4', ['en','fr','de']);   // ↴
$sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3); // ↲

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
$sitemap->addAlternates('http://example.com/%s/other', ['en','fr','de']); // ↴
$staticSitemap->addItem('http://example.com/other');                      // ↲

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

Options
-------

There are methods to configure `Sitemap` instance:
 
- `setMaxUrls($number)`. Sets maximum number of URLs to write in a single file.
  Default is 50000 which is the limit according to specification and most of
  existing implementations.
- `setBufferSize($number)`. Sets number of URLs to be kept in memory before writing it to file.
  Default is 1000. If you have more memory consider increasing it. If 1000 URLs doesn't fit,
  decrease it.
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
