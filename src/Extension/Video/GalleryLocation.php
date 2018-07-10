<?php

namespace SamDark\Sitemap\Extension\Video;

/**
 * Represents a link to the gallery (collection of videos) in which this video appears.
 * Only one <video:gallery_loc> tag can be listed for each video. The optional attribute title indicates the title of the gallery.
 *
 */
class GalleryLocation
{
    private $link;
    private $title;
}