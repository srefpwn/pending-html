
<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

header('Content-Type: application/json; charset=utf-8');

function sendUpdateResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendUpdateResponse(405, [
        'success' => false,
        'message' => 'Nem támogatott kérés.'
    ]);
}


$carId = filter_input(INPUT_POST, 'car', FILTER_VALIDATE_INT);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$partId = filter_input(INPUT_POST, 'part_id', FILTER_VALIDATE_INT);
$quantityInput = trim((string)($_POST['quantity'] ?? ''));

if (
    $carId === false || $carId === null || $carId <= 0 ||
    $partId === false || $partId === null || $partId <= 0 ||
    $quantityInput === '' ||
    !preg_match('/^[0-9]+(?:[.,][0-9])?$/', $quantityInput) ||
    (float)str_replace(',', '.', $quantityInput) <= 0
) {
    sendUpdateResponse(400, [
        'success' => false,
        'message' => 'Érvénytelen adatok.'
    ]);
}

$quantityInput = str_replace(',', '.', $quantityInput);





/*
 * Felhasználó azonosítása.
 * Másik felhasználó autóját csak admin kezelheti.
 */
if ($userId !== false && $userId !== null) {
    if (!isAdmin() || $userId <= 0) {
        sendUpdateResponse(403, [
            'success' => false,
            'message' => 'Nincs jogosultságod ehhez a művelethez.'
        ]);
    }

    if (getUserById($userId) === null) {
        sendUpdateResponse(404, [
            'success' => false,
            'message' => 'A felhasználó nem található.'
        ]);
    }
} else {
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        sendUpdateResponse(401, [
            'success' => false,
            'message' => 'Bejelentkezés szükséges.'
        ]);
    }
}

/*
 * Ellenőrizzük, hogy az autó valóban ehhez
 * a felhasználóhoz tartozik-e.
 */
$carsData = loadUserCarsData();
$userCars = $carsData[(string)$userId] ?? [];

$car = null;

foreach ($userCars as $userCar) {
    if (
        isset($userCar['id']) &&
        (int)$userCar['id'] === $carId
    ) {
        $car = $userCar;
        break;
    }
}

if ($car === null) {
    sendUpdateResponse(403, [
        'success' => false,
        'message' => 'Az autó nem tartozik ehhez a felhasználóhoz.'
    ]);
}

$carConfig = getCarConfig($car);

if ($carConfig === null || !isCarPartsEnabled($carConfig)) {
    sendUpdateResponse(403, [
        'success' => false,
        'message' => 'Az alkatrészkezelés nincs engedélyezve ehhez az autóhoz.'
    ]);
}


/*
 * A megfelelő autó alkatrészfájlja.
 */
$partsFile = $_SERVER['DOCUMENT_ROOT']
    . '/data/parts/'
    . $userId
    . '/'
    . $carId
    . '.json';

if (!is_file($partsFile) || !is_readable($partsFile)) {
    sendUpdateResponse(404, [
        'success' => false,
        'message' => 'Az alkatrészlista nem található.'
    ]);
}

$file = fopen($partsFile, 'r+');

if ($file === false) {
    sendUpdateResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista nem nyitható meg.'
    ]);
}

/*
 * Kizárólagos fájlzárolás a módosítás idejére.
 */
if (!flock($file, LOCK_EX)) {
    fclose($file);

    sendUpdateResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista nem zárolható.'
    ]);
}

$contents = stream_get_contents($file);
$parts = json_decode($contents, true);

if (
    $contents === false ||
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($parts)
) {
    flock($file, LOCK_UN);
    fclose($file);

    sendUpdateResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista JSON-fájlja hibás.'
    ]);
}

$partFound = false;
$currentQuantity = null;

foreach ($parts as &$part) {
    if (
        is_array($part) &&
        isset($part['id']) &&
        (int)$part['id'] === $partId
    ) {
        $partFound = true;
        
$unit = (string)($part['unit'] ?? 'db');

if ($unit === 'db') {
    if (!preg_match('/^[0-9]+$/', $quantityInput)) {
        unset($part);
        flock($file, LOCK_UN);
        fclose($file);

        sendUpdateResponse(400, [
            'success' => false,
            'message' => 'A darabszám csak egész szám lehet.'
        ]);
    }

    $quantity = (int)$quantityInput;
} elseif (in_array($unit, ['l', 'kg'], true)) {
    $quantity = (float)$quantityInput;
} else {
    unset($part);
    flock($file, LOCK_UN);
    fclose($file);

    sendUpdateResponse(400, [
        'success' => false,
        'message' => 'Ismeretlen mértékegység.'
    ]);
}

$currentQuantity = (float)($part['quantity'] ?? -1);

if ($currentQuantity === (float)$quantity) {
    unset($part);
    flock($file, LOCK_UN);
    fclose($file);

    sendUpdateResponse(400, [
        'success' => false,
        'message' => 'Az új mennyiség megegyezik a jelenlegivel.'
    ]);
}

$part['quantity'] = $quantity;

            unset($part);
            flock($file, LOCK_UN);
            fclose($file);

            sendUpdateResponse(400, [
                'success' => false,
                'message' => 'Az új darabszám megegyezik a jelenlegivel.'
            ]);
        }

        $part['quantity'] = $quantity;
        break;
    }
}
unset($part);

if (!$partFound) {
    flock($file, LOCK_UN);
    fclose($file);

    sendUpdateResponse(404, [
        'success' => false,
        'message' => 'Az alkatrész nem található.'
    ]);
}

$json = json_encode(
    $parts,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    flock($file, LOCK_UN);
    fclose($file);

    sendUpdateResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista nem alakítható JSON-formátumba.'
    ]);
}

rewind($file);

$written = fwrite($file, $json);
$writeSuccess = $written !== false
    && $written === strlen($json)
    && ftruncate($file, strlen($json))
    && fflush($file);

flock($file, LOCK_UN);
fclose($file);

if (!$writeSuccess) {
    sendUpdateResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista mentése sikertelen.'
    ]);
}

sendUpdateResponse(200, [
    'success' => true,
    'quantity' => $quantity,
    'message' => 'A darabszám sikeresen frissült.'
]);
