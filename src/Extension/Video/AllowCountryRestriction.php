<?php


namespace SamDark\Sitemap\Extension\Video;


class AllowCountryRestriction extends CountryRestriction
{

    public function areAllowed(): bool
    {
        return true;
    }
}