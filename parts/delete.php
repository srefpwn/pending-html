
<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

header('Content-Type: application/json; charset=utf-8');

function sendDeleteResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendDeleteResponse(405, [
        'success' => false,
        'message' => 'Nem támogatott kérés.'
    ]);
}

$carId = filter_input(INPUT_POST, 'car', FILTER_VALIDATE_INT);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$partId = filter_input(INPUT_POST, 'part_id', FILTER_VALIDATE_INT);

if (
    $carId === false || $carId === null || $carId <= 0 ||
    $partId === false || $partId === null || $partId <= 0
) {
    sendDeleteResponse(400, [
        'success' => false,
        'message' => 'Érvénytelen adatok.'
    ]);
}

/*
 * A felhasználó azonosítása.
 * Másik felhasználó alkatrészeit csak admin törölheti.
 */
if ($userId !== false && $userId !== null) {
    if (!isAdmin() || $userId <= 0) {
        sendDeleteResponse(403, [
            'success' => false,
            'message' => 'Nincs jogosultságod ehhez a művelethez.'
        ]);
    }

    if (getUserById($userId) === null) {
        sendDeleteResponse(404, [
            'success' => false,
            'message' => 'A felhasználó nem található.'
        ]);
    }
} else {
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        sendDeleteResponse(401, [
            'success' => false,
            'message' => 'Bejelentkezés szükséges.'
        ]);
    }
}

/*
 * Ellenőrizzük az autó tulajdonosát.
 */
$carsData = loadUserCarsData();
$userCars = $carsData[(string)$userId] ?? [];
$carExists = false;

foreach ($userCars as $userCar) {
    if (
        isset($userCar['id']) &&
        (int)$userCar['id'] === $carId
    ) {
        $carExists = true;
        break;
    }
}

if (!$carExists) {
    sendDeleteResponse(403, [
        'success' => false,
        'message' => 'Az autó nem tartozik ehhez a felhasználóhoz.'
    ]);
}

/*
 * Az alkatrészfájl meghatározása.
 */
$partsFile = $_SERVER['DOCUMENT_ROOT']
    . '/data/parts/'
    . $userId
    . '/'
    . $carId
    . '.json';

if (!is_file($partsFile) || !is_readable($partsFile)) {
    sendDeleteResponse(404, [
        'success' => false,
        'message' => 'Az alkatrészlista nem található.'
    ]);
}

$file = fopen($partsFile, 'r+');

if ($file === false) {
    sendDeleteResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista nem nyitható meg.'
    ]);
}

if (!flock($file, LOCK_EX)) {
    fclose($file);

    sendDeleteResponse(500, [
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

    sendDeleteResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista JSON-fájlja hibás.'
    ]);
}

/*
 * Kizárólag a kiválasztott egyedi azonosítójú
 * alkatrész törlése.
 */
$partFound = false;

foreach ($parts as $index => $part) {
    if (
        is_array($part) &&
        isset($part['id']) &&
        (int)$part['id'] === $partId
    ) {
        unset($parts[$index]);
        $partFound = true;
        break;
    }
}

if (!$partFound) {
    flock($file, LOCK_UN);
    fclose($file);

    sendDeleteResponse(404, [
        'success' => false,
        'message' => 'Az alkatrész nem található.'
    ]);
}

/*
 * Az indexek újrarendezése után az üres lista [] lesz.
 */
$parts = array_values($parts);

$json = json_encode(
    $parts,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    flock($file, LOCK_UN);
    fclose($file);

    sendDeleteResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista nem alakítható JSON-formátumba.'
    ]);
}

rewind($file);

$length = strlen($json);
$written = 0;
$writeSuccess = true;

while ($written < $length) {
    $result = fwrite($file, substr($json, $written));

    if ($result === false || $result === 0) {
        $writeSuccess = false;
        break;
    }

    $written += $result;
}

if (
    $writeSuccess &&
    (!ftruncate($file, $length) || !fflush($file))
) {
    $writeSuccess = false;
}

flock($file, LOCK_UN);
fclose($file);

if (!$writeSuccess) {
    sendDeleteResponse(500, [
        'success' => false,
        'message' => 'Az alkatrészlista mentése sikertelen.'
    ]);
}

sendDeleteResponse(200, [
    'success' => true,
    'part_id' => $partId,
    'empty' => empty($parts),
    'message' => 'Az alkatrész sikeresen törölve.'
]);
