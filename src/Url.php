<?php
namespace samdark\sitemap;

/**
 * Sitemap URL
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
     * @see https://stackoverflow.com/questions/417142/what-is-the-maximum-length-of-a-url-in-different-browsers
     */
    private $maxUrlLength = 2047;

    private $location;
    private $lastModified;
    private $changeFrequency;
    private $priority;

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Url constructor.
     * @param string $location location item URL
     * @throws \InvalidArgumentException
     */
    public function __construct($location)
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
    }

    /**
     * @return mixed
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @param integer $lastModified last modification timestamp
     * @return $this
     */
    public function lastModified($lastModified)
    {
        $this->lastModified = $lastModified;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * @param string $changeFrequency change frquency. Use one of self:: contants here
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function changeFrequency($changeFrequency)
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
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param float $priority item's priority (0.0-1.0). Default null is equal to 0.5
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function priority($priority)
    {
        if (!is_numeric($priority) || $priority < 0 || $priority > 1) {
            throw new \InvalidArgumentException(
                "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: {$priority}."
            );
        }
        $this->priority = $priority;
        return $this;
    }
}
