<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

/**
 * Egy autó alkatrészlistájának betöltése.
 *
 * Hiányzó fájl esetén üres listát ad vissza.
 * Hibás JSON esetén kivételt dob.
 */
function loadCarParts(int $userId, int $carId): array
{
    $partsFile = $_SERVER['DOCUMENT_ROOT']
        . '/data/parts/'
        . $userId
        . '/'
        . $carId
        . '.json';

    if (!file_exists($partsFile)) {
        return [];
    }

    if (!is_file($partsFile) || !is_readable($partsFile)) {
        throw new RuntimeException(
            'Az alkatrészlista fájlja nem olvasható.'
        );
    }

    $json = file_get_contents($partsFile);

    if ($json === false) {
        throw new RuntimeException(
            'Az alkatrészlista betöltése sikertelen.'
        );
    }

    $data = json_decode($json);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        throw new RuntimeException(
            'Az alkatrészlista JSON-fájlja hibás.'
        );
    }

    $parts = [];

    foreach ($data as $part) {
        if (
            !is_object($part) ||
            !isset(
                $part->id,
                $part->part_number,
                $part->name,
                $part->quantity
            ) ||
            !is_numeric($part->id) ||
            !is_numeric($part->quantity)
        ) {
            throw new RuntimeException(
                'Az alkatrészlista egyik tétele hibás.'
            );
        }

        $parts[] = [
            'id' => (int)$part->id,
            'part_number' => (string)$part->part_number,
            'name' => (string)$part->name,
    		'category' => (string)($part->category ?? ''),
            'quantity' => (int)$part->quantity
        ];
    }

    return $parts;
}
