Sitemap
=======

Very simple abstraction for sitemap generation.

How to use it
-------------

use samdark\sitemap;

$sitemap = new Sitemap();
 
// add page
$sitemap->addItem(new SitemapItem(
    'http://rmcreative.ru/', // URL
    time(), // last modifcation timestamp
    Item::DAILY, // update frequency
    0.7 // priority
));
 
// add more pages
foreach ($pages as $page){
    $sitemap->addItem(new SitemapItem(
        'http://rmcreative.ru/' . $page->url,
        $page->updatedOn,
        SitemapItem::MONTHLY
    ));
}
 
// generate sitemap.xml
$sitemap->writeToFile('sitemap.xml');

// or get it as string
$sitemapString = $sitemap->render();
