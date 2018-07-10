<?php


namespace SamDark\Sitemap\Extension;


/**
 * AlternateLink points to an alternative version of the page.
 * Used in multi-language sitemap to point crawler to an alternate language page via xhtml:link tag.
 *
 * @see https://support.google.com/webmasters/answer/2620865?hl=en
 */
class AlternateLink implements ExtensionInterface
{
    /**
     * @var string language of the page
     */
    private $language;

    /**
     * @var string URL of the page
     */
    private $location;

    /**
     * AlternateLink constructor.
     * @param string $language language of the page
     * @param string $location URL of the page
     */
    public function __construct(string $language, string $location)
    {
        $this->language = $language;
        $this->location = $location;
    }

    /**
     * Get language of the page
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get URL of the page
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function writeXmlNamepsace(\XMLWriter $writer): void
    {
        $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
    }

    /**
     * @inheritdoc
     */
    public function write(\XMLWriter $writer): void
    {
        $writer->startElement('xhtml:link');
        $writer->startAttribute('rel');
        $writer->text('alternate');
        $writer->endAttribute();

        $writer->startAttribute('hreflang');
        $writer->text($this->language);
        $writer->endAttribute();

        $writer->startAttribute('href');
        $writer->text($this->location);
        $writer->endAttribute();
        $writer->endElement();
    }
}
