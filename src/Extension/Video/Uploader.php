<?php

namespace SamDark\Sitemap\Extension\Video;


class Uploader
{
    /**
     * @var string The video uploader's name.
     */
    private $name;

    /**
     * @var string Specifies the URL of a webpage with additional information.
     * This URL must be on the same domain as the video location.
     */
    private $infoUrl;

    /**
     * Uploader constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getInfoUrl(): string
    {
        return $this->infoUrl;
    }

    /**
     * @param string $infoUrl
     * @return self
     */
    public function setInfoUrl(string $infoUrl): self
    {
        $this->infoUrl = $infoUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }




}