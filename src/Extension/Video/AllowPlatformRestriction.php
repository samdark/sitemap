<?php

namespace SamDark\Sitemap\Extension\Video;


class AllowPlatformRestriction extends PlatformRestriction
{

    public function areAllowed(): bool
    {
        return true;
    }
}