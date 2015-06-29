<?php
namespace samdark\sitemap;

/**
 * A class for generating Sitemaps (http://www.sitemaps.org/)
 *
 * @author Alexander Makarov
 * @copyright 2008
 * @link http://rmcreative.ru/blog/post/sitemap-klass-dlja-php5
 */
class Sitemap
{
    const HEAD = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    const FOOT = "\t</urlset>";

    /**
     * @var Item[]
     */
    private $items = [];

    /**
     * Escapes sitemap entities according to spec.
     *
     * @param string $var
     * @return string
     */
    private static function escapeEntites($var)
    {
        $entities = [
            '&' => '&amp;',
            "'" => '&apos;',
            '"' => '&quot;',
            '>' => '&gt;',
            '<' => '&lt;'
        ];
        return str_replace(array_keys($entities), array_values($entities), $var);
    }

    /**
     * Adds a new item to sitemap
     *
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->items[] = $item;
    }

    /**
     * Renders sitemap
     *
     * @return string
     */
    public function render()
    {
        ob_start();
        echo self::HEAD, "\n";

        foreach ($this->items as $item) {
            echo "\t\t<url>\n\t\t\t<loc>", self::escapeEntites($item->getLocation()), "</loc>\n";

            if ($item->getLastModified() !== null) {
                echo "\t\t\t<lastmod>", $item->getLastModified(), "</lastmod>\n";
            }

            if ($item->getChangeFrequency() !== null) {
                echo "\t\t\t<changefreq>", $item->getChangeFrequency(), "</changefreq>\n";
            }

            if ($item->getPriority() !== null) {
                echo "\t\t\t<priority>", $item->getPriority(), "</priority>\n";
            }

            echo "\t\t</url>\n";
        }

        echo self::FOOT, "\n";
        return ob_get_clean();
    }

    /**
     * Writes sitemap into file
     * @param string $path
     * @return boolean if write succeeded
     */
    public function writeToFile($path)
    {
        return file_put_contents($path, $this->render()) !== false;
    }
}
