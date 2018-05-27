<?php

namespace SamDark\Sitemap\Extension\Video;

use SamDark\Sitemap\Extension\ExtensionInterface;

/**
 * Video extension
 * @see https://developers.google.com/webmasters/videosearch/sitemaps
 */
class Video implements ExtensionInterface
{
    const MAX_TAGS = 32;

    /**
     * @var string A URL pointing to the video thumbnail image file.
     * Images must be at least 160x90 pixels and at most 1920x1080 pixels.
     * We recommend images in .jpg, .png, or. gif formats.
     */
    private $thumbnailLocation;

    /**
     * @var string The title of the video.
     * Recommended that this match the video title displayed on the web page.
     */
    private $title;

    /**
     * @var string
     * The description of the video. Maximum 2048 characters.
     * Must match the description displayed on the web page (need not be a
     * word-for-word match).
     */
    private $description;

    private $contentLocations = [];

    private $playerLocations = [];

    /**
     * @var int Duration of the video in seconds
     */
    private $duration;

    private $expirationDate;

    /**
     * @var float The rating of the video.
     * Allowed values are float numbers in the range 0.0 to 5.0.
     */
    private $rating;

    /**
     * @var int The number of times the video has been viewed.
     */
    private $viewCount;

    /**
     * @var \DateTimeInterface The date the video was first published.
     */
    private $publictionDate;

    /**
     * @var bool If the video should be available only to users with SafeSearch turned off.
     */
    private $familyFriendly;

    /**
     * @var CountryRestriction
     */
    private $restriction;

    /**
     * @var GalleryLocation
     */
    private $galleryLocation;


    /**
     * @var Price[]
     */
    private $prices;

    /**
     * @var bool Indicates whether a subscription (either paid or free) is required to view the video.
     */
    private $requiresSubscribtion;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var bool Indicates whether the video is a live stream.
     */
    private $live;

    private $tagsCount = 0;

    /**
     * @var array A tag associated with the video.
     * Tags are generally very short descriptions of key concepts associated
     * with a video or piece of content. A single video could have several
     * tags. For example, a video about grilling food could be tagged
     * "steak", "meat", "summer", and "outdoor".
     * A maximum of 32 tags is permitted.
     */
    private $tags = [];

    /**
     * @var string The video's category.
     * For example, cooking. The value should be a string no longer than 256 characters.
     * In general, categories are broad groupings of content by subject. Usually a video
     * will belong to a single category. For example, a site about cooking could have
     * categories for Broiling, Baking, and Grilling.
     */
    private $category;

    /**
     * Video constructor.
     * @param string $thumbnailLocation
     * @param string $title
     * @param string $description
     */
    public function __construct(string $thumbnailLocation, string $title, string $description)
    {
        $this->thumbnailLocation = $thumbnailLocation;
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * @inheritdoc
     */
    public static function getLimit(): ?int
    {
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function write(\XMLWriter $writer): void
    {
        // TODO: Implement write() method.
    }

    /**
     * @inheritdoc
     */
    public static function writeXmlNamepsace(\XMLWriter $writer): void
    {
        $writer->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
    }

    /**
     * @return string
     */
    public function getContentLocations()
    {
        return $this->contentLocations;
    }

    /**
     * @param string $contentLocation
     * @return self
     */
    public function addContentLocation($contentLocation)
    {
        $this->contentLocations[] = $contentLocation;

        return $this;
    }

    /**
     * @return array
     */
    public function getPlayerLocations()
    {
        return $this->playerLocations;
    }

    /**
     * @param string $playerLocation
     * @return self
     */
    public function addPlayerLocation($playerLocation): self
    {
        $this->playerLocations[] = $playerLocation;
        return $this;
    }

    /**
     * @return int Duration of the video in seconds
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration Duration of the video in seconds
     * @return self
     */
    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param mixed $expirationDate
     * @return self
     */
    public function setExpirationDate($expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param mixed $rating
     * @return self
     */
    public function setRating($rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getViewCount()
    {
        return $this->viewCount;
    }

    /**
     * @param mixed $viewCount
     * @return self
     */
    public function setViewCount($viewCount): self
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublictionDate()
    {
        return $this->publictionDate;
    }

    /**
     * @param mixed $publictionDate
     * @return self
     */
    public function setPublictionDate($publictionDate): self
    {
        $this->publictionDate = $publictionDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFamilyFriendly()
    {
        return $this->familyFriendly;
    }

    /**
     * @param mixed $familyFriendly
     * @return self
     */
    public function setFamilyFriendly($familyFriendly): self
    {
        $this->familyFriendly = $familyFriendly;
        return $this;
    }

    /**
     * @return CountryRestriction
     */
    public function getRestriction(): CountryRestriction
    {
        return $this->restriction;
    }

    /**
     * @param CountryRestriction $restriction
     * @return self
     */
    public function setRestriction(CountryRestriction $restriction): self
    {
        $this->restriction = $restriction;
        return $this;
    }

    /**
     * @return GalleryLocation
     */
    public function getGalleryLocation(): GalleryLocation
    {
        return $this->galleryLocation;
    }

    /**
     * @param GalleryLocation $galleryLocation
     * @return self
     */
    public function setGalleryLocation(GalleryLocation $galleryLocation): self
    {
        $this->galleryLocation = $galleryLocation;
        return $this;
    }

    /**
     * @return Price[]
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * @param Price $price
     * @return self
     */
    public function addPrice(Price $price): self
    {
        $this->prices[] = $price;

        return $this;
    }

    /**
     * @return bool
     */
    public function requiresSubscribtion(): bool
    {
        return $this->requiresSubscribtion;
    }

    /**
     * @param bool $requiresSubscribtion
     * @return self
     */
    public function setRequiresSubscribtion(bool $requiresSubscribtion): self
    {
        $this->requiresSubscribtion = $requiresSubscribtion;

        return $this;
    }

    /**
     * @return Uploader
     */
    public function getUploader(): Uploader
    {
        return $this->uploader;
    }

    /**
     * @param Uploader $uploader
     * @return self
     */
    public function setUploader(Uploader $uploader): self
    {
        $this->uploader = $uploader;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLive(): bool
    {
        return $this->live;
    }

    /**
     * @param bool $live
     * @return self
     */
    public function setLive(bool $live): self
    {
        $this->live = $live;

        return $this;
    }


}