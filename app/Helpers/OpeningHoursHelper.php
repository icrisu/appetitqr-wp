<?php
namespace AppetitQR\Helpers;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Port of app/m/[locationSlug]/_templates/default/openingHours.ts.
 *
 * Kept faithful to the original — including how overnight intervals (22:00–02:00) count
 * for both the day they start and the early hours of the next — so the "Open now" badge
 * on a WordPress page agrees with the one on the diner-facing menu.
 */
class OpeningHoursHelper {

    const DAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** "HH:MM" -> minutes since midnight, or null when unparseable. */
    private static function toMinutes(mixed $time): ?int {
        if (!is_string($time)) {
            return null;
        }

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time), $matches)) {
            return null;
        }

        $hh = (int) $matches[1];
        $mm = (int) $matches[2];
        if ($hh > 23 || $mm > 59) {
            return null;
        }

        return $hh * 60 + $mm;
    }

    private static function prevDay(string $day): string {
        $index = array_search($day, self::DAY_KEYS, true);
        if ($index === false) {
            return self::DAY_KEYS[0];
        }

        return self::DAY_KEYS[($index + count(self::DAY_KEYS) - 1) % count(self::DAY_KEYS)];
    }

    private static function intervalsFor(array $hours, string $day): array {
        $schedule = $hours[$day] ?? null;
        if (!is_array($schedule) || empty($schedule['enabled'])) {
            return [];
        }

        return is_array($schedule['intervals'] ?? null) ? $schedule['intervals'] : [];
    }

    private static function isOpenAt(array $hours, string $day, int $nowMin): bool {
        foreach (self::intervalsFor($hours, $day) as $interval) {
            $from = self::toMinutes($interval['from'] ?? null);
            $to   = self::toMinutes($interval['to'] ?? null);
            if ($from === null || $to === null) {
                continue;
            }

            if ($to > $from) {
                if ($nowMin >= $from && $nowMin < $to) {
                    return true;
                }
            } elseif ($nowMin >= $from) {
                // Overnight interval: open from `from` until end of day.
                return true;
            }
        }

        // Early-morning tail of an overnight interval that began yesterday.
        foreach (self::intervalsFor($hours, self::prevDay($day)) as $interval) {
            $from = self::toMinutes($interval['from'] ?? null);
            $to   = self::toMinutes($interval['to'] ?? null);
            if ($from === null || $to === null) {
                continue;
            }

            if ($to <= $from && $nowMin < $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * True/false when the location's hours decide it, null when hours should not gate
     * anything at all (no timezone, no hours, or no enabled day).
     */
    static function isOpenNow(mixed $workingHours, ?string $timezone): ?bool {
        if (empty($timezone) || !is_array($workingHours)) {
            return null;
        }

        $hasEnabledDay = false;
        foreach (self::DAY_KEYS as $day) {
            if (!empty($workingHours[$day]['enabled'])) {
                $hasEnabledDay = true;
                break;
            }
        }
        if (!$hasEnabledDay) {
            return null;
        }

        try {
            $now = new \DateTime('now', new \DateTimeZone($timezone));
        } catch (\Throwable $th) {
            // Invalid timezone — don't gate rather than break the page.
            return null;
        }

        $day    = strtolower($now->format('D'));
        $nowMin = ((int) $now->format('G')) * 60 + ((int) $now->format('i'));

        if (!in_array($day, self::DAY_KEYS, true)) {
            return null;
        }

        return self::isOpenAt($workingHours, $day, $nowMin);
    }

    /**
     * Normalised day rows for the info block: [['key' => 'mon', 'intervals' => [...]], …]
     */
    static function getSchedule(mixed $workingHours): array {
        if (!is_array($workingHours)) {
            return [];
        }

        $rows = [];
        foreach (self::DAY_KEYS as $day) {
            $rows[] = [
                'key'       => $day,
                'enabled'   => !empty($workingHours[$day]['enabled']),
                'intervals' => self::intervalsFor($workingHours, $day),
            ];
        }

        return $rows;
    }

    static function dayLabel(string $key): string {
        return match ($key) {
            'mon'   => esc_html__('Monday', 'appetitqr'),
            'tue'   => esc_html__('Tuesday', 'appetitqr'),
            'wed'   => esc_html__('Wednesday', 'appetitqr'),
            'thu'   => esc_html__('Thursday', 'appetitqr'),
            'fri'   => esc_html__('Friday', 'appetitqr'),
            'sat'   => esc_html__('Saturday', 'appetitqr'),
            'sun'   => esc_html__('Sunday', 'appetitqr'),
            default => '',
        };
    }
}
