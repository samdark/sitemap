<?php
namespace samdark\sitemap;

/**
 * A sitemap item
 *
 * @author Alexander Makarov
 * @copyright 2008
 * @link http://rmcreative.ru/blog/post/sitemap-klass-dlja-php5
 */
class Item
{
    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

    /**
     * @var string location item URL
     */
    private $location;

    /**
     * @var integer Last modification timestamp
     */
    private $lastModified;

    /**
     * @var string change frquency
     */
    private $changeFrequency;

    /**
     * @var float item's priority
     */
    private $priority;

    /**
     * @param string $location location item URL.
     * @param integer $lastModified last modification timestamp.
     * @param string $changeFrequency change frquency. Use one of self:: contants here.
     * @param float $priority item's priority (0.0-1.0). Default null is equal to 0.5.
     */
    function __construct($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        $this->location = $location;
        if ($lastModified !== null) {
            $this->lastModified = date('c', $lastModified);
        }
        $this->changeFrequency = $changeFrequency;
        $this->priority = $priority;
    }

    /**
     * @return string location item URL
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return null|integer last modification timestamp
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return null|string change frquency
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * @return float|null item's priority
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
