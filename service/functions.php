<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';


/*
 * Szervizkönyvek adatkönyvtára
 */
define(
    'SERVICE_DATA_DIR',
    $_SERVER['DOCUMENT_ROOT'] . '/data/service/'
);


/*
 * Bejelentkezett felhasználó ID-ja
 */
function getCurrentUserId(): ?string
{
    if (
        !isset($_SESSION['user_id']) ||
        !is_numeric($_SESSION['user_id'])
    ) {
        return null;
    }

    $userId = (int)$_SESSION['user_id'];

    if ($userId <= 0) {
        return null;
    }

    return (string)$userId;
}


/*
 * Bejelentkezés ellenőrzése
 */
function requireLogin(): string
{
    $userId = getCurrentUserId();

    if ($userId === null) {
        denyAccess();
    }

    return $userId;
}


/*
 * Saját autó lekérése ID alapján
 *
 * Kizárólag az aktuális felhasználó autói között keres.
 */
function getServiceCar(int $carId): ?array
{
    if ($carId <= 0) {
        return null;
    }

    $cars = getUserCars();

    foreach ($cars as $car) {

        if (
            isset($car['id']) &&
            (int)$car['id'] === $carId
        ) {
            return $car;
        }
    }

    return null;
}


/*
 * Autó ellenőrzése
 *
 * Ha az autó nem létezik vagy nem a felhasználóhoz tartozik,
 * hozzáférés megtagadva.
 */
function requireServiceCar(int $carId): array
{
    requireLogin();

    $car = getServiceCar($carId);

    if ($car === null) {
        denyAccess();
    }

    return $car;
}


/*
 * Szervizkönyv fájl útvonala
 *
 * Az ID-k egész számmá alakítása megakadályozza,
 * hogy a fájlútvonalba tetszőleges karakter kerüljön.
 */
function getServiceFile(string $userId, int $carId): string
{
    $userId = (string)(int)$userId;
    $carId = (int)$carId;

    return SERVICE_DATA_DIR
        . '/'
        . $userId
        . '/'
        . $carId
        . '.json';
}


/*
 * Szervizkönyv könyvtár létrehozása
 */
function ensureServiceDirectory(string $userId): bool
{
    $userId = (string)(int)$userId;

    if ((int)$userId <= 0) {
        return false;
    }

    $directory =
        SERVICE_DATA_DIR
        . '/'
        . $userId;

    if (is_dir($directory)) {
        return true;
    }

    return mkdir(
        $directory,
        0750,
        true
    );
}


/*
 * Üres szervizkönyv szerkezete
 */
function getEmptyServiceData(int $carId): array
{
    return [
        'car_id' => $carId,
        'entries' => []
    ];
}


/*
 * Szervizkönyv adatainak alapvető szerkezeti ellenőrzése
 *
 * Nem engedünk át tetszőleges JSON-struktúrát.
 */
function validateServiceData(
    array $data,
    int $carId
): bool {

    /*
     * car_id kötelező
     */
    if (
        !isset($data['car_id']) ||
        !is_numeric($data['car_id']) ||
        (int)$data['car_id'] !== $carId
    ) {
        return false;
    }


    /*
     * entries kötelező és tömb kell legyen
     */
    if (
        !isset($data['entries']) ||
        !is_array($data['entries'])
    ) {
        return false;
    }


    /*
     * Bejegyzések ellenőrzése
     */
    foreach ($data['entries'] as $entry) {

        if (!is_array($entry)) {
            return false;
        }


        /*
         * Kötelező mezők
         */
        if (
            !isset($entry['id']) ||
            !is_numeric($entry['id'])
        ) {
            return false;
        }


        if (
            !isset($entry['date']) ||
            !is_string($entry['date']) ||
            $entry['date'] === ''
        ) {
            return false;
        }


        if (
            !isset($entry['title']) ||
            !is_string($entry['title']) ||
            $entry['title'] === ''
        ) {
            return false;
        }


        /*
         * km opcionális,
         * de ha létezik, egész számnak kell lennie.
         */
        if (
            isset($entry['km']) &&
            $entry['km'] !== '' &&
            (
                !is_numeric($entry['km']) ||
                (int)$entry['km'] < 0
            )
        ) {
            return false;
        }


        /*
         * description opcionális
         */
        if (
            isset($entry['description']) &&
            !is_string($entry['description'])
        ) {
            return false;
        }


        /*
         * type
         */
        if (
            !isset($entry['type']) ||
            !in_array(
                $entry['type'],
                ['simple', 'detailed'],
                true
            )
        ) {
            return false;
        }


        /*
         * items
         */
        if (
            !isset($entry['items']) ||
            !is_array($entry['items'])
        ) {
            return false;
        }


        /*
         * Költségek
         */
        if (
            !isset($entry['labor_cost']) ||
            !is_numeric($entry['labor_cost']) ||
            (float)$entry['labor_cost'] < 0
        ) {
            return false;
        }


        if (
            !isset($entry['total_cost']) ||
            !is_numeric($entry['total_cost']) ||
            (float)$entry['total_cost'] < 0
        ) {
            return false;
        }


        /*
         * Alkatrész tételek
         */
        foreach ($entry['items'] as $item) {

            if (!is_array($item)) {
                return false;
            }


            if (
                !isset($item['part_number']) ||
                !is_string($item['part_number'])
            ) {
                return false;
            }


            if (
                !isset($item['name']) ||
                !is_string($item['name']) ||
                $item['name'] === ''
            ) {
                return false;
            }


            if (
                !isset($item['quantity']) ||
                !is_numeric($item['quantity']) ||
                (float)$item['quantity'] <= 0
            ) {
                return false;
            }


            if (
                !isset($item['unit_price']) ||
                !is_numeric($item['unit_price']) ||
                (float)$item['unit_price'] < 0
            ) {
                return false;
            }


            if (
                !isset($item['total_price']) ||
                !is_numeric($item['total_price']) ||
                (float)$item['total_price'] < 0
            ) {
                return false;
            }
        }
    }


    return true;
}


/*
 * Szervizkönyv betöltése
 *
 * FONTOS:
 *
 * - nem létező fájl = normális, üres szervizkönyv
 * - üres fájl = hiba
 * - hibás JSON = hiba
 * - hibás struktúra = hiba
 *
 * Hibás adat esetén NEM adunk vissza üres tömböt.
 */
function loadServiceData(
    string $userId,
    int $carId
): array {

    $file = getServiceFile(
        $userId,
        $carId
    );


    /*
     * Még nincs szervizkönyv.
     *
     * Ez teljesen normális állapot.
     */
    if (!file_exists($file)) {

        return [
            'success' => true,
            'data' => getEmptyServiceData($carId),
            'error' => null
        ];
    }


    /*
     * Fájl olvasása
     */
    $json = file_get_contents($file);

    if ($json === false) {

        return [
            'success' => false,
            'data' => null,
            'error' => 'A szervizkönyv fájlja nem olvasható.'
        ];
    }


    /*
     * Üres fájl nem tekinthető üres szervizkönyvnek.
     */
    if (trim($json) === '') {

        return [
            'success' => false,
            'data' => null,
            'error' => 'A szervizkönyv fájlja üres vagy sérült.'
        ];
    }


    /*
     * JSON dekódolás
     */
    $data = json_decode(
        $json,
        true
    );


    /*
     * Hibás JSON
     */
    if (
        !is_array($data) ||
        json_last_error() !== JSON_ERROR_NONE
    ) {

        return [
            'success' => false,
            'data' => null,
            'error' => 'A szervizkönyv adatai sérültek.'
        ];
    }


    /*
     * Struktúra ellenőrzése
     */
    if (
        !validateServiceData(
            $data,
            $carId
        )
    ) {

        return [
            'success' => false,
            'data' => null,
            'error' => 'A szervizkönyv adatszerkezete érvénytelen.'
        ];
    }


    /*
     * Minden rendben.
     */
    return [
        'success' => true,
        'data' => $data,
        'error' => null
    ];
}


/*
 * Szervizkönyv mentése
 *
 * Csak érvényes struktúra menthető.
 */
function saveServiceData(
    string $userId,
    int $carId,
    array $data
): bool {

    /*
     * Adatstruktúra ellenőrzése
     */
    if (
        !validateServiceData(
            $data,
            $carId
        )
    ) {
        return false;
    }


    /*
     * Könyvtár létrehozása
     */
    if (
        !ensureServiceDirectory(
            $userId
        )
    ) {
        return false;
    }


    /*
     * Fájl
     */
    $file = getServiceFile(
        $userId,
        $carId
    );


    /*
     * JSON készítése
     */
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_THROW_ON_ERROR
    );


    /*
     * Mentés
     */
    return file_put_contents(
        $file,
        $json,
        LOCK_EX
    ) !== false;
}

/*
 * Következő szervizbejegyzés ID
 */
function getNextServiceEntryId(
    array $entries
): int {

    $nextId = 1;

    foreach ($entries as $entry) {

        if (
            isset($entry['id']) &&
            is_numeric($entry['id'])
        ) {

            $nextId = max(
                $nextId,
                (int)$entry['id'] + 1
            );
        }
    }

    return $nextId;
}

/*
 * Új szervizbejegyzés dátum- és kilométeróra-állásának ellenőrzése
 *
 * A szervizkönyvben időben és kilométerben csak előre haladhatunk.
 *
 * A függvény nem feltételezi, hogy az entries tömb sorrendben van.
 * Mindig megkeresi a legutóbbi dátumú érvényes bejegyzést.
 */
function validateServiceEntryOrder(
    array $entries,
    string $date,
    ?int $km
): array {

    if (empty($entries)) {
        return [
            'success' => true,
            'error' => ''
        ];
    }


    /*
     * Legutóbbi bejegyzés keresése dátum alapján
     */
    $lastEntry = null;
    $lastDate = null;

    foreach ($entries as $entry) {

        if (!is_array($entry)) {
            continue;
        }

        $entryDate = trim(
            (string)($entry['date'] ?? '')
        );

        if ($entryDate === '') {
            continue;
        }

        if (
            $lastDate === null ||
            $entryDate > $lastDate
        ) {
            $lastDate = $entryDate;
            $lastEntry = $entry;
        }
    }


    /*
     * Ha nincs értelmezhető korábbi dátum,
     * nincs mihez viszonyítani.
     */
    if ($lastEntry === null) {
        return [
            'success' => true,
            'error' => ''
        ];
    }


    /*
     * Dátum ellenőrzése
     */
    if ($date < $lastDate) {

        return [
            'success' => false,
            'error' =>
                'A szervizbejegyzés dátuma nem lehet korábbi az előző bejegyzés dátumánál.'
        ];
    }


    /*
     * Kilométeróra-állás ellenőrzése
     *
     * Csak akkor hasonlítjuk össze,
     * ha az új és az előző bejegyzésnél is van km.
     */
    if (
        $km !== null &&
        isset($lastEntry['km']) &&
        $lastEntry['km'] !== '' &&
        is_numeric($lastEntry['km'])
    ) {

        $lastKm = (int)$lastEntry['km'];

        if ($km < $lastKm) {

            return [
                'success' => false,
                'error' =>
                    'A kilométeróra-állás nem lehet kisebb az előző szervizbejegyzés értékénél.'
            ];
        }
    }


    /*
     * Minden ellenőrzés sikeres
     */
    return [
        'success' => true,
        'error' => ''
    ];
}

/*
 * CSRF token
 */
function getServiceCsrfToken(): string
{
    if (
        empty(
            $_SESSION['service_csrf_token']
        )
    ) {

        $_SESSION['service_csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION[
        'service_csrf_token'
    ];
}


/*
 * CSRF ellenőrzése
 */
function verifyServiceCsrf(): bool
{
    if (
        !isset($_POST['csrf_token']) ||
        !isset(
            $_SESSION['service_csrf_token']
        )
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['service_csrf_token'],
        $_POST['csrf_token']
    );
}


/*
 * Szervizkönyv biztonságos kontextus lekérése
 *
 * Ellenőrzi:
 *
 * - be van-e jelentkezve
 * - érvényes-e az autó ID
 * - az autó valóban a felhasználóhoz tartozik
 */
function getServiceContext(
    int $carId
): array {

    $userId = requireLogin();


    if ($carId <= 0) {
        denyAccess();
    }


    $car = requireServiceCar(
        $carId
    );


    return [
        'user_id' => $userId,
        'car_id' => $carId,
        'car' => $car
    ];
}


/*
 * Hozzáférés megtagadása
 */
function denyAccess(): never
{
    http_response_code(403);

    require __DIR__ . '/access_denied.php';

    exit;
}
