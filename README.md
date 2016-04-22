Sitemap
=======

Sitemap and sitemap index builder.

<img src="https://travis-ci.org/samdark/sitemap.svg" />

Features
--------

- Create sitemap files.
- Create sitemap index files.
- Automatically creates new file if 50000 URLs limit or 10 MB limit is reached.
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
use samdark\sitemap;

// create sitemap
$sitemap = new Sitemap(__DIR__ . '/sitemap.xml');

// add some URLs
$sitemap->addItem(new Url('http://example.com/mylink1'));
$sitemap->addItem(
    (new Url('http://example.com/mylink2'))
        ->lastModified(time())
);
$sitemap->addItem(
    (new Url('http://example.com/mylink3'))
        ->lastModified(time())
        ->changeFrequency(Url::HOURLY)
);
$sitemap->addItem(
    (new Url('http://example.com/mylink4'))
        ->lastModified(time())
        ->changeFrequency(Url::DAILY)
        ->priority(0.3)
);

// write it
$sitemap->write();

// get URLs of sitemaps written
$sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

// create sitemap for static files
$staticSitemap = new Sitemap(__DIR__ . '/sitemap_static.xml');

// add some URLs
$staticSitemap->addItem(new Url('http://example.com/about'));
$staticSitemap->addItem(new Url('http://example.com/tos'));
$staticSitemap->addItem(new Url('http://example.com/jobs'));

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

There are two methods to configre `Sitemap` instance:
 
- `setMaxUrls($number)`. Sets maximum number of URLs to write in a single file.
  Default is 50000 which is the limit according to specification and most of
  existing implementations.
- `setBufferSize($number)`. Sets number of URLs to be kept in memory before writing it to file.
  Default is 1000. If you have more memory consider increasing it. If 1000 URLs doesn't fit,
  decrease it.
- `setMaxFileSize($bytes)`. Sets maximum allowed number of bytes per single file.
  Default is 10485760 bytes which equals to 10 megabytes.

Running tests
-------------

In order to run tests perform the following commands:

```
composer install
phpunit
```
