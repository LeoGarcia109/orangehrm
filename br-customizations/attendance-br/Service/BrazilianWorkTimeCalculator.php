<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Attendance\Service;

use DateTime;

/**
 * Calculates Brazilian labor law work time components per CLT and Portaria 673/2021:
 *
 * - Overtime (horas extras): hours beyond the contractual daily/weekly journey
 * - Night shift bonus (adicional noturno): 20% surcharge for urban work 22:00-05:00
 * - Reduced night hour (hora reduzida noturna): 52min30s counts as 1 full hour
 * - Intrajourney interval (intervalo intrajornada): minimum 1h for journeys > 6h
 * - Inter-journey interval (intervalo interjornada): minimum 11h between workdays
 */
class BrazilianWorkTimeCalculator
{
    public const NIGHT_BONUS_URBAN_PERCENT = 20.0;
    public const NIGHT_BONUS_RURAL_PERCENT = 25.0;
    public const OVERTIME_FIRST_2H_PERCENT = 50.0;
    public const OVERTIME_BEYOND_2H_PERCENT = 100.0;
    public const SUNDAY_HOLIDAY_PERCENT = 100.0;

    private const NIGHT_START_HOUR = 22;
    private const NIGHT_END_HOUR = 5;
    private const NIGHT_HOUR_SECONDS = 3150; // 52min30s
    private const MIN_INTRAJOURNEY_INTERVAL_SECONDS = 3600;
    private const MIN_INTERJOURNEY_INTERVAL_SECONDS = 39600; // 11h
    private const INTRAJOURNEY_THRESHOLD_SECONDS = 21600; // 6h

    private float $nightBonusPercent;
    private float $overtimeFirst2hPercent;
    private float $overtimeBeyond2hPercent;
    private int $dailyJourneySeconds;
    private int $weeklyJourneySeconds;

    /**
     * @param int $dailyJourneyMinutes Contractual daily journey (default 480 = 8h)
     * @param int $weeklyJourneyMinutes Contractual weekly journey (default 2640 = 44h)
     * @param float $nightBonusPercent Night bonus percentage (default 20% urban)
     * @param float $overtimeFirst2hPercent OT % for first 2 extra hours (default 50%)
     * @param float $overtimeBeyond2hPercent OT % beyond 2 extra hours (default 100%)
     */
    public function __construct(
        int $dailyJourneyMinutes = 480,
        int $weeklyJourneyMinutes = 2640,
        float $nightBonusPercent = self::NIGHT_BONUS_URBAN_PERCENT,
        float $overtimeFirst2hPercent = self::OVERTIME_FIRST_2H_PERCENT,
        float $overtimeBeyond2hPercent = self::OVERTIME_BEYOND_2H_PERCENT
    ) {
        $this->dailyJourneySeconds = $dailyJourneyMinutes * 60;
        $this->weeklyJourneySeconds = $weeklyJourneyMinutes * 60;
        $this->nightBonusPercent = $nightBonusPercent;
        $this->overtimeFirst2hPercent = $overtimeFirst2hPercent;
        $this->overtimeBeyond2hPercent = $overtimeBeyond2hPercent;
    }

    /**
     * Calculate all work time components for a single day's punch records.
     *
     * @param array $punchPairs Array of ['in' => DateTime, 'out' => DateTime] pairs
     * @param bool $isSundayOrHoliday Whether the day is a Sunday or holiday
     * @return array Complete breakdown of the work day
     */
    public function calculateDay(array $punchPairs, bool $isSundayOrHoliday = false): array
    {
        $totalWorkedSeconds = 0;
        $totalNightSeconds = 0;

        foreach ($punchPairs as $pair) {
            $in = $pair['in'];
            $out = $pair['out'];
            $totalWorkedSeconds += $out->getTimestamp() - $in->getTimestamp();
            $totalNightSeconds += $this->calculateNightSeconds($in, $out);
        }

        // Reduced night hours (52min30s = 1h)
        $nightHoursReduced = $totalNightSeconds / self::NIGHT_HOUR_SECONDS;

        $intrajourney = $this->checkIntrajourneyInterval($punchPairs, $totalWorkedSeconds);
        $overtime = $this->calculateOvertime($totalWorkedSeconds, $isSundayOrHoliday);

        return [
            'totalWorkedSeconds' => $totalWorkedSeconds,
            'totalWorkedFormatted' => self::formatDuration($totalWorkedSeconds),
            'nightSeconds' => $totalNightSeconds,
            'nightHoursReduced' => round($nightHoursReduced, 4),
            'nightBonusPercent' => $this->nightBonusPercent,
            'overtimeFirst2hSeconds' => $overtime['first2hSeconds'],
            'overtimeFirst2hFormatted' => self::formatDuration($overtime['first2hSeconds']),
            'overtimeFirst2hPercent' => $overtime['first2hPercent'],
            'overtimeBeyond2hSeconds' => $overtime['beyond2hSeconds'],
            'overtimeBeyond2hFormatted' => self::formatDuration($overtime['beyond2hSeconds']),
            'overtimeBeyond2hPercent' => $overtime['beyond2hPercent'],
            'intrajourneyCompliant' => $intrajourney['compliant'],
            'intrajourneyIntervalSeconds' => $intrajourney['intervalSeconds'],
            'intrajourneyDeficitSeconds' => $intrajourney['deficitSeconds'],
            'isSundayOrHoliday' => $isSundayOrHoliday,
        ];
    }

    /**
     * Calculate seconds worked during the night period (22:00-05:00).
     */
    public function calculateNightSeconds(DateTime $in, DateTime $out): int
    {
        $nightSeconds = 0;
        $current = clone $in;

        while ($current < $out) {
            $hour = (int)$current->format('G');
            $isNightHour = ($hour >= self::NIGHT_START_HOUR || $hour < self::NIGHT_END_HOUR);

            if ($isNightHour) {
                $nextBoundary = (clone $current)->modify('+1 hour');
                $nextBoundary->setTime((int)$nextBoundary->format('G'), 0, 0);
                $segmentEnd = min($nextBoundary, $out);
                $nightSeconds += $segmentEnd->getTimestamp() - $current->getTimestamp();
            }

            $current = (clone $current)->modify('+1 hour');
            $current->setTime((int)$current->format('G'), 0, 0);
            if ($current > $out) {
                break;
            }
        }

        return $nightSeconds;
    }

    /**
     * Check intrajourney interval compliance.
     * For journeys > 6h, minimum 1h interval required between punch pairs.
     */
    public function checkIntrajourneyInterval(array $punchPairs, int $totalWorkedSeconds): array
    {
        if ($totalWorkedSeconds <= self::INTRAJOURNEY_THRESHOLD_SECONDS) {
            return ['compliant' => true, 'intervalSeconds' => 0, 'deficitSeconds' => 0];
        }

        $maxInterval = 0;
        for ($i = 1; $i < count($punchPairs); $i++) {
            $gap = $punchPairs[$i]['in']->getTimestamp() - $punchPairs[$i - 1]['out']->getTimestamp();
            $maxInterval = max($maxInterval, $gap);
        }

        $compliant = $maxInterval >= self::MIN_INTRAJOURNEY_INTERVAL_SECONDS;
        $deficit = $compliant ? 0 : (self::MIN_INTRAJOURNEY_INTERVAL_SECONDS - $maxInterval);

        return [
            'compliant' => $compliant,
            'intervalSeconds' => $maxInterval,
            'deficitSeconds' => $deficit,
        ];
    }

    /**
     * Check inter-journey interval (minimum 11h between workdays).
     */
    public function checkInterjourneyInterval(DateTime $previousDayOut, DateTime $currentDayIn): array
    {
        $interval = $currentDayIn->getTimestamp() - $previousDayOut->getTimestamp();
        $compliant = $interval >= self::MIN_INTERJOURNEY_INTERVAL_SECONDS;
        $deficit = $compliant ? 0 : (self::MIN_INTERJOURNEY_INTERVAL_SECONDS - $interval);

        return [
            'compliant' => $compliant,
            'intervalSeconds' => $interval,
            'deficitSeconds' => $deficit,
        ];
    }

    /**
     * Calculate overtime split: first 2h at 50%, beyond at 100%.
     */
    private function calculateOvertime(int $totalWorkedSeconds, bool $isSundayOrHoliday): array
    {
        if ($isSundayOrHoliday) {
            return [
                'first2hSeconds' => $totalWorkedSeconds,
                'beyond2hSeconds' => 0,
                'first2hPercent' => self::SUNDAY_HOLIDAY_PERCENT,
                'beyond2hPercent' => 0.0,
            ];
        }

        $excess = max(0, $totalWorkedSeconds - $this->dailyJourneySeconds);
        $first2h = min($excess, 7200);
        $beyond2h = max(0, $excess - 7200);

        return [
            'first2hSeconds' => $first2h,
            'beyond2hSeconds' => $beyond2h,
            'first2hPercent' => $this->overtimeFirst2hPercent,
            'beyond2hPercent' => $this->overtimeBeyond2hPercent,
        ];
    }

    /**
     * Calculate time bank balance for a period.
     * Positive = credit (worked more), Negative = debit (worked less).
     */
    public function calculateTimeBankBalance(int $totalWorkedSeconds, int $expectedSeconds): int
    {
        return $totalWorkedSeconds - $expectedSeconds;
    }

    /**
     * Get expected work seconds for N business days.
     */
    public function getExpectedSecondsForDays(int $businessDays): int
    {
        return $businessDays * $this->dailyJourneySeconds;
    }

    /**
     * Format seconds as HH:MM:SS.
     */
    public static function formatDuration(int $seconds): string
    {
        $negative = $seconds < 0;
        $seconds = abs($seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return ($negative ? '-' : '') . sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
