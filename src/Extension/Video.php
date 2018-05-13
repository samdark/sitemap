<?php

namespace SamDark\Sitemap\Extension;

/**
 * Video
 * @see https://developers.google.com/webmasters/videosearch/sitemaps
 */
class Video implements ExtensionInterface
{
    private $thumbnailLocation;
    private $title;
    private $description;
    private $contentLocation;
    private $playerLocation;
    private $duration;
    private $expirationDate;
    private $rating;
    private $viewCount;
    private $publictionDate;
    private $familyFriendly;
    private $restriction;
    private $galleryLocation;
    private $price;
    private $requiresSubscribtion;
    private $uploader;
    private $live;

    /**
     * Video constructor.
     * @param $thumbnailLocation
     * @param $title
     * @param $description
     */
    public function __construct($thumbnailLocation, $title, $description)
    {
        $this->thumbnailLocation = $thumbnailLocation;
        $this->title = $title;
        $this->description = $description;
    }


    public function getLimit(): ?int
    {
        // TODO: Implement getLimit() method.
    }

    public function getNamespace(): string
    {
        return 'video';
    }

    public function getUrl(): string
    {
        return 'http://www.google.com/schemas/sitemap-video/1.1';
    }

    public function write(\XMLWriter $writer)
    {
        // TODO: Implement write() method.
    }

    public function writeXmlNamepsace(\XMLWriter $writer)
    {
        // TODO: Implement writeXmlNamepsace() method.
    }
}