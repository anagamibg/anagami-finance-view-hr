<?php

namespace App\Helper;

class AttendanceLocation
{
    /**
     * Whether the request must include valid GPS coordinates.
     */
    public static function isRequired(object $settings, ?string $workFromType): bool
    {
        if (!empty($settings->save_current_location)) {
            return true;
        }

        return ($settings->radius_check ?? null) === 'yes' && $workFromType !== 'home';
    }

    /**
     * Parse and validate latitude/longitude from the client.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public static function parseCoordinates($latitude, $longitude): ?array
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return null;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    /**
     * Distance in meters between two WGS84 points (haversine).
     *
     * Spherical law of cosines is unstable for near-identical points and can
     * yield acos() of a value slightly above 1 (NAN), which fails radius checks
     * for employees standing at the office pin.
     */
    public static function distanceInMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($fromLat);
        $lonFrom = deg2rad($fromLng);
        $latTo = deg2rad($toLat);
        $lonTo = deg2rad($toLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function isWithinRadius(float $fromLat, float $fromLng, float $toLat, float $toLng, $radiusMeters): bool
    {
        if (!is_numeric($radiusMeters) || (float) $radiusMeters < 0) {
            return false;
        }

        return self::distanceInMeters($fromLat, $fromLng, $toLat, $toLng) <= (float) $radiusMeters;
    }
}
