<?php

namespace SamDark\Sitemap\Extension\Video;


abstract class PlatformRestriction
{
    public const WEB = 'web';
    public const MOBILE = 'mobile';
    public const TV = 'tv';

    abstract public function areAllowed(): bool;
}