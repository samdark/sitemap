<?php

namespace SamDark\Sitemap\Extension\Video;


class DenyCountryRestriction extends CountryRestriction
{
    public function areAllowed(): bool
    {
        return false;
    }
}