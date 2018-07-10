<?php

namespace SamDark\Sitemap\Extension\Video;


class Price
{
    public const TYPE_RENT = 'rent';
    public const TYPE_OWN = 'own';

    public const RESOLUTION_HD = 'hd';
    public const RESOLUTION_SD = 'sd';

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $resolution;

    /**
     * @var float
     */
    private $value;

    /**
     * Price constructor.
     * @param string $currency
     * @param float $value
     */
    public function __construct(string $currency, float $value)
    {
        $this->currency = $currency;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getResolution(): string
    {
        return $this->resolution;
    }

    /**
     * @param string $resolution
     */
    public function setResolution(string $resolution): void
    {
        $this->resolution = $resolution;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }




}