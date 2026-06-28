<?php

function getCoordinates($address)
{
    $address = urlencode($address);

    $url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

    $opts = [
        "http" => [
            "header" => "User-Agent: FASTGO/1.0\r\n"
        ]
    ];

    $context = stream_context_create($opts);

    $response = file_get_contents($url, false, $context);

    if (!$response) {
        return false;
    }

    $data = json_decode($response, true);

    if (empty($data)) {
        return false;
    }

    return [
        'lat' => $data[0]['lat'],
        'lng' => $data[0]['lon']
    ];
}
//tính kc tự động
function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a =
        sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *
        sin($dLon / 2) *
        sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($earthRadius * $c, 2);
}