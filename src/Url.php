<?php
namespace samdark\sitemap;

/**
 * Sitemap URL
 * 
 * @see http://sitemaps.org/protocol.html#xmlTagDefinitions
 */
class Url
{
    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

    /**
     * @var array valid values for frequency parameter
     */
    private $validFrequencies = [
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER
    ];

    /**
     * @var integer maximum URL length
     */
    private $maxUrlLength = 2047;

    private $location;
    private $lastModified;
    private $changeFrequency;
    private $priority;

    /**
     * Url constructor.
     * @param string $location URL of the page.
     * @param string $lastModified the date of last modification of the file
     * @param string $changeFrequency how frequently the page is likely to change
     * @param string|float $priority the priority of this URL relative to other
     * URLs on site
     * @throws \InvalidArgumentException
     */
    public function __construct($location, $lastModified = null, $changeFrequency = null, $priority = null)
    {
        if (false === filter_var($location, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                "The location must be a valid URL. You have specified: {$location}."
            );
        }
        if (mb_strlen($location, 'UTF-8') > $this->maxUrlLength) {
            $maxLength = $this->maxUrlLength + 1;
            throw new \InvalidArgumentException(
                "The location must be less than $maxLength characters."
            );
        }
        $this->location = $location;
        
        if ($lastModified) {
            $this->setLastModified($lastModified);
        }
        if ($changeFrequency) {
            $this->setChangeFrequency($changeFrequency);
        }
        if ($priority !== null) {
            $this->setPriority($priority);
        }
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string|null
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets a date of last modification of the file. This date should be in
     * W3C Datetime format.
     * @see http://w3.org/TR/NOTE-datetime
     * @param string $lastModified
     * @return $this
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = (string)$lastModified;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * Sets how frequently the page is likely to change. Use one of class
     * contants here.
     * @param string $changeFrequency
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setChangeFrequency($changeFrequency)
    {
        if (!in_array($changeFrequency, $this->validFrequencies, true)) {
            throw new \InvalidArgumentException(
                'Please specify valid changeFrequency. Valid values are: '
                . implode(', ', $this->validFrequencies)
                . "You have specified: {$changeFrequency}."
            );
        }
        $this->changeFrequency = $changeFrequency;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Sets a priority of this URL relative to other URLs on your site. Valid values
     * range from 0.0 to 1.0. The default priority of a page is 0.5.
     * @param string|float $priority
     * @throws \InvalidArgumentException
     * @return self
     */
    public function setPriority($priority)
    {
        if (!is_numeric($priority) || $priority < 0 || $priority > 1) {
            throw new \InvalidArgumentException(
                "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: {$priority}."
            );
        }
        $this->priority = number_format($priority, 1, '.', ',');
        return $this;
    }
}
