<?php
declare(strict_types=1);

namespace App\Utils;

use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;

class SentryTime
{
    /**
     * Get the start date of the current Sentry Quarter
     */
    public static function getStartOfCurrentQuarter(): DateTime
    {
        $now = DateTime::now();

        /**
         * Sentry's quarters are shifted by a month.
         * Q1 ranges from February to April, etc.
         */
        $oneMonthAgo = $now->subMonths(1);
        $sentryQuarter = $oneMonthAgo->toQuarter();
        $startOfQuarterMonth = (($sentryQuarter - 1) * Chronos::MONTHS_PER_QUARTER + 1) + 1;

        $startOfQuarter = DateTime::create(
            year: $oneMonthAgo->year,
            month: $startOfQuarterMonth,
            day: 1,
        )->startOfDay();

        return $startOfQuarter;
    }
}
