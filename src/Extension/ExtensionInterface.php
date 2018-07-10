<?php

namespace SamDark\Sitemap\Extension;

/**
 * ItemInterface represents a custom sitemap Url tag
 * @see https://www.sitemaps.org/protocol.html#extending
 */
interface ExtensionInterface
{
    /**
     * @return int|null maximum number of extension tag occurences per Url
     */
    public static function getLimit(): ?int;

    /**
     * Writes XML namespace attribute
     * @param \XMLWriter $writer
     */
    public static function writeXmlNamepsace(\XMLWriter $writer): void;

    /**
     * Writes extension tag
     * @param \XMLWriter $writer
     */
    public function write(\XMLWriter $writer): void;
}
