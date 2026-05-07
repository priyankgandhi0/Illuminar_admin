<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Handles timezone-aware date/time conversion for the admin panel.
 *
 * Rule: Admin always works in Brazil time (America/Sao_Paulo).
 * Storage: All datetimes are saved as UTC ISO 8601 strings.
 * Display: UTC values are converted back to Brazil time before showing to admin.
 */
class DateTimeService
{
    const BRAZIL_TZ = 'America/Sao_Paulo';

    /**
     * Convert a Brazil local date + time to a UTC ISO 8601 string for storage.
     *
     * @param  string       $date  Date in YYYY-MM-DD format (Brazil local)
     * @param  string|null  $time  Time in HH:MM format (Brazil local); null = 00:00
     * @return string  UTC ISO 8601 string, e.g. "2026-04-01T11:50:00+00:00"
     *
     * Example:
     *   Admin enters: 01-04-2026 08:50 (Brazil = UTC-3)
     *   Stored as:    2026-04-01T11:50:00+00:00
     */
    public static function brazilToUtc(string $date, ?string $time = null): string
    {
        $dt = Carbon::parse($date, self::BRAZIL_TZ)->startOfDay();

        if ($time) {
            [$h, $m] = explode(':', $time);
            $dt->setHour((int) $h)->setMinute((int) $m)->setSecond(0);
        }

        return $dt->utc()->toIso8601String();
    }

    /**
     * Convert a stored UTC ISO string to a Carbon instance in Brazil timezone.
     *
     * @param  string  $utcDatetime  UTC ISO 8601 string (from Firestore)
     * @return Carbon  Carbon instance in America/Sao_Paulo timezone
     */
    public static function utcToBrazil(string $utcDatetime): Carbon
    {
        return Carbon::parse($utcDatetime)->setTimezone(self::BRAZIL_TZ);
    }

    /**
     * Format a Carbon instance for admin display: dd-mm-yyyy HH:MM
     *
     * @param  Carbon  $dt
     * @return string  e.g. "01-04-2026 08:50"
     */
    public static function formatForDisplay(Carbon $dt): string
    {
        return $dt->format('d-m-Y H:i');
    }

    /**
     * Get the current moment in Brazil timezone.
     */
    public static function brazilNow(): Carbon
    {
        return Carbon::now(self::BRAZIL_TZ);
    }

    /**
     * Check whether a stored UTC datetime is in the past (i.e. publish time has passed).
     *
     * @param  string  $utcDatetime  UTC ISO 8601 string
     * @return bool
     */
    public static function isUtcInThePast(string $utcDatetime): bool
    {
        return Carbon::parse($utcDatetime)->lte(Carbon::now('UTC'));
    }
}
