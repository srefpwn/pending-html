<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';


/*
 * Felhasználói autók JSON fájl
 */
define(
    'USER_CARS_FILE',
    $_SERVER['DOCUMENT_ROOT'] . '/cars/data/user_cars.json'
);


/*
 * JSON adatok betöltése
 */
function loadUserCarsData(): array
{
    if (!file_exists(USER_CARS_FILE)) {
        return [];
    }

    $json = file_get_contents(USER_CARS_FILE);

    if ($json === false || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}


/*
 * JSON adatok mentése
 */
function saveUserCarsData(array $data): bool
{
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return false;
    }

    return file_put_contents(
        USER_CARS_FILE,
        $json,
        LOCK_EX
    ) !== false;
}


/*
 * Aktuális felhasználó autóinak lekérése
 */
function getUserCars(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [];
    }

    $userId = (string)$_SESSION['user_id'];

    $data = loadUserCarsData();

    if (!isset($data[$userId]) || !is_array($data[$userId])) {
        return [];
    }

    return $data[$userId];
}


/*
 * VIN normalizálása
 */
function normalizeVin(string $vin): string
{
    return strtoupper(
        preg_replace('/[^A-Z0-9]/i', '', trim($vin))
    );
}


/*
 * VIN keresése a konfigurációk között
 */
function getVinConfig(string $vin): ?array
{
    global $vin_configs;

    $vin = normalizeVin($vin);

    if ($vin === '') {
        return null;
    }

    if (!isset($vin_configs[$vin])) {
        return null;
    }

    return $vin_configs[$vin];
}


/*
 * Autó hozzáadása
 */
function addUserCar(
    string $vin,
    string $name = '',
    string $productionYear = '',
    string $productionMonth = '',
    string $body = '',
    string $engine = '',
    string $trim = '',
    string $color = '',
    string $colorCode = ''
): array
{
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => false,
            'message' => 'Nincs bejelentkezett felhasználó.'
        ];
    }

$vin = normalizeVin($vin);

$name = trim($name);

if (mb_strlen($name, 'UTF-8') > 50) {
    return [
        'success' => false,
        'message' => 'Az autó neve legfeljebb 50 karakter lehet.'
    ];
}

/*
 * VIN ellenőrzése
 */
if ($vin === '') {
    return [
        'success' => false,
        'message' => 'Az alvázszám megadása kötelező.'
    ];
}

    /*
     * VIN konfiguráció keresése
     */
    $config = getVinConfig($vin);

    if ($config === null) {
        return [
            'success' => false,
            'message' => 'A megadott alvázszámhoz nem található konfiguráció.'
        ];
    }

    $userId = (string)$_SESSION['user_id'];

    $data = loadUserCarsData();

    if (!isset($data[$userId]) || !is_array($data[$userId])) {
        $data[$userId] = [];
    }

    /*
     * Ellenőrizzük, hogy ezt az autót
     * nem adta-e már hozzá a felhasználó.
     */
    foreach ($data[$userId] as $car) {

        if (
            isset($car['vin']) &&
            normalizeVin($car['vin']) === $vin
        ) {
            return [
                'success' => false,
                'message' => 'Ez az autó már szerepel az autóid között.'
            ];
        }
    }

    /*
     * Következő ID meghatározása
     */
    $nextId = 1;

    foreach ($data[$userId] as $car) {

        if (
            isset($car['id']) &&
            is_numeric($car['id'])
        ) {
            $nextId = max(
                $nextId,
                (int)$car['id'] + 1
            );
        }
    }

    /*
     * Új autó
     */
$data[$userId][] = [
    'id' => $nextId,
    'name' => $name,
    'vin' => $vin,
    'production_year' => $productionYear,
    'production_month' => $productionMonth,
    'body' => $body,
    'engine' => $engine,
    'trim' => $trim,
    'color' => $color,
    'color_code' => $colorCode
];

    /*
     * Mentés
     */
    if (!saveUserCarsData($data)) {
        return [
            'success' => false,
            'message' => 'Az autó mentése sikertelen.'
        ];
    }

    return [
        'success' => true,
        'message' => 'Az autó sikeresen hozzáadva.'
    ];
}


/*
 * Autó törlése
 */
function deleteUserCar(int $carId): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $userId = (string)$_SESSION['user_id'];

    $data = loadUserCarsData();

    if (!isset($data[$userId]) || !is_array($data[$userId])) {
        return false;
    }

    foreach ($data[$userId] as $index => $car) {

        if (
            isset($car['id']) &&
            (int)$car['id'] === $carId
        ) {

            unset($data[$userId][$index]);

            $data[$userId] = array_values(
                $data[$userId]
            );

            return saveUserCarsData($data);
        }
    }

    return false;
}


/*
 * Autó konfigurációjának lekérése
 */
function getCarConfig(array $car): ?array
{
    if (!isset($car['vin'])) {
        return null;
    }

    return getVinConfig($car['vin']);
}
