<?php

namespace SamDark\Sitemap;

use SamDark\Sitemap\Extension\ExtensionInterface;

/**
 * A single page URL within a sitemap
 */
class Url
{
    /**
     * @var string URL of the page
     */
    private $location;

    /**
     * @var \DateTimeInterface last modification timestamp
     */
    private $lastModified;

    /**
     * @var string change frequency. Use one of constants from Frequency
     */
    private $changeFrequency;

    /**
     * @var string priority (0.0-1.0). Default is 0.5.
     */
    private $priority = 0.5;

    /**
     * @var array used to count items added
     */
    private $itemCounters = [];

    /**
     * @var ExtensionInterface[]
     */
    private $extensionItems = [];

    /**
     * Item constructor.
     * @param $location
     */
    public function __construct(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     * @return Url
     */
    public function setLocation(string $location): Url
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastModified(): ?\DateTimeInterface
    {
        return $this->lastModified;
    }

    /**
     * @param \DateTimeInterface $lastModified
     * @return Url
     */
    public function setLastModified(\DateTimeInterface $lastModified): Url
    {
        $this->lastModified = $lastModified;
        return $this;
    }

    /**
     * @return string
     */
    public function getChangeFrequency(): ?string
    {
        return $this->changeFrequency;
    }

    /**
     * @param string $changeFrequency
     * @return Url
     * @throws \InvalidArgumentException
     */
    public function setChangeFrequency(string $changeFrequency): Url
    {
        if (!\in_array($changeFrequency, Frequency::all(), true)) {
            throw new \InvalidArgumentException(
                'Please specify valid changeFrequency. Valid values are: '
                . implode(', ', Frequency::all())
                . "You have specified: {$changeFrequency}."
            );
        }

        $this->changeFrequency = $changeFrequency;
        return $this;
    }

    /**
     * @return float
     */
    public function getPriority(): float
    {
        return $this->priority;
    }

    /**
     * @param float $priority
     * @return Url
     * @throws \InvalidArgumentException
     */
    public function setPriority(float $priority): Url
    {
        if ($priority < 0 || $priority > 1) {
            throw new \InvalidArgumentException(
                "Please specify valid priority. Valid values range from 0.0 to 1.0. You have specified: {$priority}."
            );
        }

        $this->priority = $priority;
        return $this;
    }

    /**
     * @param ExtensionInterface $item
     * @return Url
     * @throws \LogicException
     */
    public function add(ExtensionInterface $item): Url
    {
        $itemClass = \get_class($item);

        $currentValue = $this->itemCounters[$itemClass] ?? 0;
        $limit = $item->getLimit();
        if ($limit !== null && $currentValue === $limit) {
            throw new \LogicException("You can not add more than $limit of $itemClass");
        }

        $this->extensionItems[] = $item;
        $this->itemCounters[$itemClass] = ++$currentValue;

        return $this;
    }

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensionItems(): array
    {
        return $this->extensionItems;
    }
}