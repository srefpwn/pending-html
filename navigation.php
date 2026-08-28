<?php

/*
 * ============================================================
 * NAVIGÁCIÓ
 * ============================================================
 *
 * Kezeli:
 *
 * - car
 * - from_cars
 * - back
 * - navigationParams
 * - returnUrl
 * - navigation history
 *
 * A type / page / category paramétereket NEM kezeli.
 */


/*
 * ============================================================
 * SESSION
 * ============================================================
 */

if (!isset($_SESSION['navigation_history'])) {
    $_SESSION['navigation_history'] = [];
}


/*
 * ============================================================
 * PARAMÉTEREK
 * ============================================================
 */

$back = $_GET['back'] ?? '';
$from_cars = $_GET['from_cars'] ?? '';

$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);


/*
 * ============================================================
 * AKTUÁLIS OLDAL
 * ============================================================
 */

$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

$currentUrl = $_SERVER['REQUEST_URI'] ?? '';


/*
 * ============================================================
 * HISTORY URL
 * ============================================================
 *
 * A history-ban nem tároljuk a back paramétert.
 *
 * Így nem keletkezik ilyen:
 *
 * /service/?car=1&back=/cars/
 *      ↓
 * /service/view.php?...&back=/service/?car=1&back=/cars/
 *
 * A history mindig az oldal saját URL-jét tárolja.
 */

$historyUrl = $currentUrl;

$historyParts = parse_url($historyUrl);

$historyPath = $historyParts['path'] ?? '/';

$historyQuery = [];

if (!empty($historyParts['query'])) {

    parse_str(
        $historyParts['query'],
        $historyQuery
    );
}


/*
 * A back paramétert kizárjuk a history-ból.
 */

unset($historyQuery['back']);


/*
 * Újra összeállítjuk a tiszta URL-t.
 */

$historyUrl = $historyPath;

if (!empty($historyQuery)) {

    $historyUrl .=
        '?' .
        http_build_query(
            $historyQuery,
            '',
            '&',
            PHP_QUERY_RFC3986
        );
}


/*
 * ============================================================
 * NAVIGATION HISTORY KEZELÉSE
 * ============================================================
 *
 * A history logikája:
 *
 * 1. Új oldal → hozzáadjuk.
 *
 * 2. Ugyanazon az oldalon maradunk →
 *    nem duplikáljuk.
 *
 * 3. Ha a jelenlegi URL a history második
 *    eleme a végéről, akkor visszalépés történt.
 *
 * Példa:
 *
 * [Cars, Service, Service view]
 *
 * Service view → Vissza
 *
 * A Service oldal újratöltésekor:
 *
 * [Cars, Service, Service view]
 *              ↑
 *
 * felismerjük, hogy visszaléptünk.
 *
 * Ekkor levesszük a Service view-t:
 *
 * [Cars, Service]
 */


/*
 * History referencia
 */

$history =& $_SESSION['navigation_history'];

$historyCount = count($history);


/*
 * History még üres.
 */

if ($historyCount === 0) {

    $history[] = $historyUrl;

}


/*
 * Ugyanaz az URL van legutolsóként.
 */

elseif (
    $history[$historyCount - 1] === $historyUrl
) {

    /*
     * Nem csinálunk semmit.
     */
}


/*
 * A jelenlegi URL a history utolsó
 * előtti eleme.
 *
 * Ez azt jelenti, hogy visszaléptünk
 * az előző oldalra.
 */

elseif (
    $historyCount >= 2 &&
    $history[$historyCount - 2] === $historyUrl
) {

    /*
     * Az aktuális előző oldal levétele.
     */

    array_pop($history);
}


/*
 * Egyébként új előremenő oldal.
 */

else {

    $history[] = $historyUrl;
}


/*
 * ============================================================
 * HISTORY KORLÁTOZÁSA
 * ============================================================
 *
 * Védelem arra az esetre, ha valaki nagyon
 * hosszú navigációs láncot épít fel.
 *
 * Az utolsó 50 oldal elegendő.
 */

if (count($history) > 50) {

    $history = array_slice(
        $history,
        -50
    );
}


/*
 * ============================================================
 * VISSZATÉRÉSI URL
 * ============================================================
 *
 * Elsődlegesen mindig a history előző eleme.
 */

$returnUrl = '';

$historyCount = count($history);

if ($historyCount >= 2) {

    $returnUrl =
        $history[$historyCount - 2];
}


/*
 * ============================================================
 * DEFAULT VISSZATÉRÉSI ÚTVONALAK
 * ============================================================
 *
 * Csak akkor használjuk őket,
 * ha nincs korábbi history elem.
 */


/*
 * /cars/
 */

if (
    $returnUrl === '' &&
    $currentScript === '/cars/index.php'
) {

    $returnUrl = '/';
}


/*
 * /service/view.php
 *
 * Ha nincs history előzmény,
 * akkor a saját service indexére térünk vissza.
 */

elseif (
    $returnUrl === '' &&
    $currentScript === '/service/view.php' &&
    $carId !== false &&
    $carId !== null
) {

    $returnUrl =
        '/service/?car=' .
        urlencode($carId);
}


/*
 * EPC autós környezet
 */

elseif (
    $returnUrl === '' &&
    $carId !== false &&
    $carId !== null &&
    !empty($from_cars) &&
    isset($type) &&
    $type !== ''
) {

    $returnUrl =
        '/7gen/epc/?page=' .
        urlencode($type) .
        '&car=' .
        urlencode($carId) .
        '&from_cars=' .
        urlencode($from_cars);
}


/*
 * Normál EPC
 */

elseif (
    $returnUrl === '' &&
    isset($type) &&
    $type !== ''
) {

    $returnUrl =
        '/7gen/epc/?page=' .
        urlencode($type);
}


/*
 * Végső biztonsági alapértelmezés.
 */

if ($returnUrl === '') {

    $returnUrl = '/';
}


/*
 * ============================================================
 * ELŐREMENŐ NAVIGÁCIÓS PARAMÉTEREK
 * ============================================================
 *
 * Ezeket NEM a history helyettesíti.
 *
 * Ezek továbbra is az aktuális környezet
 * szükséges paramétereit viszik tovább.
 */


/*
 * Kezdőérték.
 */

$navigationParams = '';


/*
 * car
 */

if (
    $carId !== false &&
    $carId !== null
) {

    $navigationParams .=
        '&car=' . urlencode($carId);
}


/*
 * back
 *
 * A következő oldal számára az aktuális
 * oldal tiszta URL-je lesz a közvetlen
 * előző oldal.
 *
 * A historyUrl-t használjuk, nem a
 * nyers REQUEST_URI-t.
 */

if (
    $historyUrl !== '' &&
    $currentScript !== '/cars/index.php'
) {

    $navigationParams .=
        '&back=' . urlencode($historyUrl);
}


/*
 * from_cars
 *
 * Ha létezik, továbbvisszük.
 */

if (!empty($from_cars)) {

    $navigationParams .=
        '&from_cars=' . urlencode($from_cars);
}


/*
 * ============================================================
 * CARS KIINDULÁSI PONT
 * ============================================================
 *
 * Ha a Cars oldalról indulunk tovább,
 * akkor a következő oldal számára
 * a Cars legyen az előző oldal.
 */

if (
    $currentScript === '/cars/index.php'
) {

    $navigationParams =
        '&back=' . urlencode('/cars/');
}

$navigationInputs = '';

if ($carId !== false && $carId !== null) {
    $navigationInputs .=
        '<input type="hidden" name="car" value="' .
        htmlspecialchars((string)$carId, ENT_QUOTES, 'UTF-8') .
        '">';
}

if (!empty($back)) {
    $navigationInputs .=
        '<input type="hidden" name="back" value="' .
        htmlspecialchars($back, ENT_QUOTES, 'UTF-8') .
        '">';
}

if (!empty($from_cars)) {
    $navigationInputs .=
        '<input type="hidden" name="from_cars" value="' .
        htmlspecialchars($from_cars, ENT_QUOTES, 'UTF-8') .
        '">';
}
