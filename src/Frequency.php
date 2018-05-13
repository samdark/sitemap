<?php

namespace SamDark\Sitemap;

/**
 * Frequency
 */
final class Frequency
{
    public const ALWAYS = 'always';
    public const HOURLY = 'hourly';
    public const DAILY = 'daily';
    public const WEEKLY = 'weekly';
    public const MONTHLY = 'monthly';
    public const YEARLY = 'yearly';
    public const NEVER = 'never';

    /**
     * @return array
     */
    public static function all(): array
    {
        return [
            self::ALWAYS,
            self::HOURLY,
            self::DAILY,
            self::WEEKLY,
            self::MONTHLY,
            self::YEARLY,
            self::NEVER
        ];
    }
}
