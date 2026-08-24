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
/*
 * VIN alapján katalógusmodell keresése
 */
function findCatalogVinModel(string $vin): ?array
{
    global $vin_configs;
    global $car_catalog;

    $vin = normalizeVin($vin);

    if ($vin === '') {
        return null;
    }

    /*
     * 1. A VIN-nek szerepelnie kell a támogatott VIN-ek között.
     */
    if (!isset($vin_configs[$vin])) {
        return null;
    }

    /*
     * 2. A támogatott VIN-hez tartozó konfiguráció.
     */
    $vinConfig = $vin_configs[$vin];

    /*
     * 3. Megkeressük a katalógusban azt a modellt,
     *    amelynek VIN szabályai illenek erre a VIN-re.
     */
    foreach ($car_catalog as $brandKey => $brandConfig) {

        foreach (($brandConfig['models'] ?? []) as $modelKey => $modelConfig) {

            $rules = $modelConfig['vin']['rules'] ?? [];

            if (!is_array($rules) || empty($rules)) {
                continue;
            }

            $matched = true;

            foreach ($rules as $rule) {

                $position = (int)($rule['position'] ?? 0) - 1;
                $length = (int)($rule['length'] ?? 0);

                if ($position < 0 || $length <= 0) {
                    continue;
                }

                $value = substr($vin, $position, $length);

                $allowedValues = $rule['values'] ?? [];

                /*
                 * Ha a VIN adott része nincs a szabályban,
                 * akkor ez nem ehhez a modellhez tartozik.
                 */
                if (!array_key_exists($value, $allowedValues)) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                return [
                    'brand' => $brandKey,
                    'model' => $modelKey,
                    'config' => $modelConfig,
                    'vin_config' => $vinConfig
                ];
            }
        }
    }

    return null;
}
/*
 * VIN adatainak dekódolása katalógus szabályok alapján
 */
function decodeCatalogVin(string $vin, array $modelConfig): array
{
    $vin = normalizeVin($vin);
    $vinValues = [];

    $rules = $modelConfig['vin']['rules'] ?? [];

    if (!is_array($rules)) {
        return $vinValues;
    }

    foreach ($rules as $rule) {
        $position = (int)($rule['position'] ?? 0) - 1;
        $length = (int)($rule['length'] ?? 0);

        if ($position < 0 || $length <= 0) {
            continue;
        }

        $value = substr($vin, $position, $length);

        $result = $rule['values'][$value] ?? null;

        if ($result === null) {
            continue;
        }

        $target = $rule['target'] ?? '';

        if ($target !== '') {
            $vinValues[$target] = $result;
        }
    }

    return $vinValues;
}

/*
 * Autó hozzáadása
 */
function addUserCar(
    string $vin,
    string $name = '',
    string $brand = '',
    string $model = '',
    string $productionYear = '',
    string $body = '',
    string $engine = '',
    string $trim = '',
    string $color = ''
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
    $brand = trim($brand);
    $model = trim($model);
    $productionYear = trim($productionYear);
    $body = trim($body);
    $engine = trim($engine);
    $trim = trim($trim);
    $color = trim($color);

    /*
     * Autó neve
     */
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
     * VIN hosszának ellenőrzése
     */
    if (strlen($vin) !== 17) {
        return [
            'success' => false,
            'message' => 'Az alvázszámnak 17 karakterből kell állnia.'
        ];
    }
/*
 * VIN ellenőrzése a támogatott konfigurációk között
 */
global $vin_configs;

if (!isset($vin_configs[$vin])) {
    return [
        'success' => false,
        'message' => 'A megadott alvázszám nem található a támogatott járművek között.'
    ];
}

    /*
     * Felhasználói adatok betöltése
     */
    $userId = (string)$_SESSION['user_id'];

    $data = loadUserCarsData();

    if (!isset($data[$userId]) || !is_array($data[$userId])) {
        $data[$userId] = [];
    }

    /*
     * Duplikált VIN ellenőrzése
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
        'brand' => $brand,
        'model' => $model,
        'production_year' => $productionYear,
        'body' => $body,
        'engine' => $engine,
        'trim' => $trim,
        'color' => $color
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
