<?php

if (! function_exists('verifyGPS')) {
    function verifyGPS(float $subLat, float $subLng, float $addrLat, float $addrLng): bool
    {
        $radius = 6371000;
        $dLat = deg2rad($addrLat - $subLat);
        $dLng = deg2rad($addrLng - $subLng);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($subLat)) * cos(deg2rad($addrLat))
            * sin($dLng / 2) * sin($dLng / 2);
        $distance = $radius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $distance <= 1000;
    }
}
