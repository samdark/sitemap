<?php

use samdark\sitemap\Index;
use samdark\sitemap\Sitemap;

class SitemapGenerationBench
{
    private $run = 0;

    public function benchSmallWebsite()
    {
        $this->generateWebsite('small', 100, 20, 10);
    }

    public function benchMediumWebsite()
    {
        $this->generateWebsite('medium', 5000, 1000, 1000);
    }

    public function benchLargeWebsite()
    {
        $this->generateWebsite('large', 60000, 10000, 13000);
    }

    private function generateWebsite($name, $contentUrlCount, $staticUrlCount, $multilingualPageCount)
    {
        $directory = $this->createRunDirectory($name);

        try {
            $contentSitemap = new Sitemap($directory . '/sitemap.xml');
            $contentSitemap->setStylesheet('http://example.com/css/sitemap.xsl');
            $this->addContentUrls($contentSitemap, $contentUrlCount);
            $contentSitemap->write();

            $staticSitemap = new Sitemap($directory . '/sitemap_static.xml');
            $staticSitemap->setStylesheet('http://example.com/css/sitemap.xsl');
            $this->addStaticUrls($staticSitemap, $staticUrlCount);
            $staticSitemap->write();

            $multilingualSitemap = new Sitemap($directory . '/sitemap_multi_language.xml', true);
            $multilingualSitemap->setMaxUrls(25000);
            $multilingualSitemap->setStylesheet('http://example.com/css/sitemap.xsl');
            $this->addMultilingualUrls($multilingualSitemap, $multilingualPageCount);
            $multilingualSitemap->write();

            $index = new Index($directory . '/sitemap_index.xml');
            $index->setStylesheet('http://example.com/css/sitemap.xsl');
            $this->addSitemapsToIndex($index, $contentSitemap);
            $this->addSitemapsToIndex($index, $staticSitemap);
            $this->addSitemapsToIndex($index, $multilingualSitemap);
            $index->write();
        } finally {
            $this->removeRunDirectory($directory);
        }
    }

    private function addContentUrls(Sitemap $sitemap, $urlCount)
    {
        $lastModified = strtotime('2024-01-01T00:00:00+00:00');

        for ($i = 1; $i <= $urlCount; $i++) {
            $sitemap->addItem(
                'http://example.com/articles/article-' . $i . '?page=' . (($i % 10) + 1),
                $lastModified + $i,
                $this->frequencyFor($i),
                $this->priorityFor($i)
            );
        }
    }

    private function addStaticUrls(Sitemap $sitemap, $urlCount)
    {
        $paths = array(
            'about',
            'tos',
            'privacy',
            'jobs',
            'contact',
            'help',
            'pricing',
            'features',
        );

        for ($i = 1; $i <= $urlCount; $i++) {
            $path = $paths[($i - 1) % count($paths)];
            $suffix = $i > count($paths) ? '-' . $i : '';
            $sitemap->addItem('http://example.com/' . $path . $suffix);
        }
    }

    private function addMultilingualUrls(Sitemap $sitemap, $pageCount)
    {
        $lastModified = strtotime('2024-01-01T00:00:00+00:00');

        for ($i = 1; $i <= $pageCount; $i++) {
            $sitemap->addItem(
                array(
                    'ru' => 'http://example.com/ru/catalog/product-' . $i,
                    'en' => 'http://example.com/en/catalog/product-' . $i,
                ),
                $lastModified + $i,
                Sitemap::DAILY,
                0.8
            );
        }
    }

    private function addSitemapsToIndex(Index $index, Sitemap $sitemap)
    {
        foreach ($sitemap->getSitemapUrls('http://example.com/') as $url) {
            $index->addSitemap($url);
        }
    }

    private function frequencyFor($i): string
    {
        if ($i % 7 === 0) {
            return Sitemap::WEEKLY;
        }

        if ($i % 3 === 0) {
            return Sitemap::HOURLY;
        }

        return Sitemap::DAILY;
    }

    private function priorityFor($i)
    {
        return (($i % 10) + 1) / 10;
    }

    private function createRunDirectory(string $name): string
    {
        $directory = sys_get_temp_dir() . '/samdark-sitemap-bench-' . getmypid() . '-' . $name . '-' . (++$this->run);

        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            throw new RuntimeException('Unable to create benchmark directory: ' . $directory);
        }

        return $directory;
    }

    private function removeRunDirectory($directory)
    {
        foreach (glob($directory . '/*') as $file) {
            unlink($file);
        }

        rmdir($directory);
    }
}
