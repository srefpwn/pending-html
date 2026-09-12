<?php

$htmlFile = __DIR__ . '/cl9.html';
$jsonFile = __DIR__ . '/cl9.json';

$html = file_get_contents($htmlFile);

if ($html === false) {
    die('Nem sikerült beolvasni a crv.html fájlt.');
}

$result = [];

/*
 * Schema blokkok keresése
 */
preg_match_all(
    '/<div[^>]+class="[^"]*epcVariation__schema[^"]*"[^>]+data-id="([^"]+)"[^>]*>(.*?)<\/div>\s*<\/div>/is',
    $html,
    $matches,
    PREG_SET_ORDER
);

foreach ($matches as $match) {

    $id   = trim($match[1]);
    $block = $match[2];

    /*
     * Név keresése
     */
    if (!preg_match(
        '/<div[^>]+class="[^"]*epcVariation__schema-name[^"]*"[^>]*>.*?<a[^>]*>(.*?)<\/a>/is',
        $block,
        $nameMatch
    )) {
        continue;
    }

    $fullTitle = trim(
        html_entity_decode(
            strip_tags($nameMatch[1]),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        )
    );

    /*
     * Előtag levágása
     *
     * Például:
     * E-3 - intake manifold(2.0l)
     *
     * eredmény:
     * intake manifold(2.0l)
     */
    $title = $fullTitle;

    if (strpos($fullTitle, ' - ') !== false) {
        [, $title] = explode(' - ', $fullTitle, 2);
        $title = trim($title);
    }

    /*
     * Kategória meghatározása
     */
    $category = '';

    $categoryMap = [
        '/engine/'             => 'Engine',
        '/transmission/'       => 'Transmission',
        '/electric/'           => 'Electrical equipments, exhaust, heater',
        '/steering/'           => 'Steering, brake, suspension',
        '/upholstery/'         => 'Upholstery',
        '/body/'               => 'Body parts',
        '/genuine_acessories/' => 'Accessories',
    ];

    /*
     * Az egész blokkban megkeressük az EPC linket
     */
    if (preg_match(
        '/href="([^"]+)"/i',
        $block,
        $hrefMatch
    )) {

        $href = $hrefMatch[1];

        foreach ($categoryMap as $path => $categoryName) {

            if (stripos($href, $path) !== false) {
                $category = $categoryName;
                break;
            }
        }
    }

    $result[] = [
        'id'       => $id,
        'title'    => $title,
        'category' => $category,
    ];
}


/*
 * JSON létrehozása
 */
$json = json_encode(
    $result,
    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    die('Nem sikerült JSON-t készíteni.');
}

if (file_put_contents($jsonFile, $json) === false) {
    die('Nem sikerült létrehozni a crv.json fájlt.');
}

echo 'Kész! ' . count($result) . ' elem került a crv.json fájlba.';
