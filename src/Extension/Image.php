<?php


namespace SamDark\Sitemap\Extension;


/**
 * Image extension
 * @see https://support.google.com/webmasters/answer/178636
 */
class Image implements ExtensionInterface
{
    private const LIMIT = 1000;

    /**
     * @var string URL of the image.
     */
    private $location;

    /**
     * @var string Caption of the image.
     */
    private $caption;

    /**
     * @var string Geographic location of the image.
     *
     * For example, "Limerick, Ireland".
     */
    private $geoLocation;

    /**
     * @var string Title of the image.
     */
    private $title;

    /**
     * @var string URL to the license of the image.
     */
    private $license;

    /**
     * Image constructor.
     * @param $location
     */
    public function __construct(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * @param string $caption
     */
    public function setCaption(string $caption): void
    {
        $this->caption = $caption;
    }

    /**
     * @return string
     */
    public function getGeoLocation(): string
    {
        return $this->geoLocation;
    }

    /**
     * @param string $geoLocation
     */
    public function setGeoLocation(string $geoLocation): void
    {
        $this->geoLocation = $geoLocation;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense(string $license): void
    {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @inheritdoc
     */
    public static function getLimit(): ?int
    {
        return self::LIMIT;
    }

    /**
     * @inheritdoc
     */
    public function write(\XMLWriter $writer): void
    {
        $writer->startElement('image:image');

        if (!empty($image['loc'])) {
            $writer->writeElement('image:loc', $image['loc']);
        }
        if (!empty($image['caption'])) {
            $writer->writeElement('image:caption', $image['caption']);
        }
        if (!empty($image['geo_location'])) {
            $writer->writeElement('image:geo_location', $image['geo_location']);
        }
        if (!empty($image['title'])) {
            $writer->writeElement('image:title', $image['title']);
        }
        if (!empty($image['license'])) {
            $writer->writeElement('image:license', $image['license']);
        }

        $writer->endElement();
    }

    /**
     * Writes XML namespace attribute
     * @param \XMLWriter $writer
     */
    public static function writeXmlNamepsace(\XMLWriter $writer): void
    {
        $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
    }
}