<?php
namespace samdark\sitemap\tests;

use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;

class AlternateHrefLangTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Not validated of simtemap according to XSD schema
	 */
	public function testWritingFile()
	{
		$fileName = __DIR__ . '/data/sitemap_a.xml';
		$sitemap = new Sitemap($fileName);
		$sitemap->addAlternates('http://example.com/%s/mylink4', ['en','fr','de',]);
		$sitemap->addItem('http://example.com/es/mylink4', time(), Sitemap::DAILY, 0.3);
		$sitemap->addItem('http://example.com/es/mylink8', time(), Sitemap::DAILY, 0.3);
		$sitemap->write();

		$this->assertTrue(file_exists($fileName));

		//unlink($fileName);
	}

	/**
	 * Not validated of simtemap according to XSD schema
	 */
	public function testWritingIndex()
	{
		// create sitemap
		$sitemap = new Sitemap(__DIR__ . '/data/sitemap_b.xml');

		// add some URLs
		$sitemap->addItem('http://example.com/mylink1');
		$sitemap->addAlternates('http://example.com/%s/mylink2', ['en','fr','de',]);
		$sitemap->addItem('http://example.com/mylink2', time());
		$sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
		$sitemap->addAlternates('http://example.com/%s/mylink4', ['en','fr','de',]);
		$sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);

		// write it
		$sitemap->write();

		// get URLs of sitemaps written
		$sitemapFileUrls = $sitemap->getSitemapUrls('http://example.com/');

		// create sitemap for static files
		$staticSitemap = new Sitemap(__DIR__ . '/data/sitemap_static.xml');

		// add some URLs
		$sitemap->addAlternates('http://example.com/%s/about', ['en','fr','de',]);
		$staticSitemap->addItem('http://example.com/about');
		$sitemap->addAlternates('http://example.com/%s/tos', ['en','fr','de',]);
		$staticSitemap->addItem('http://example.com/tos');
		$sitemap->addAlternates('http://example.com/%s/jobs', ['en','fr','de',]);
		$staticSitemap->addItem('http://example.com/jobs');

		// write it
		$staticSitemap->write();

		// get URLs of sitemaps written
		$staticSitemapUrls = $staticSitemap->getSitemapUrls('http://example.com/');

		// create sitemap index file
		$index = new Index(__DIR__ . '/data/sitemap_index.xml');

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
	}
}
