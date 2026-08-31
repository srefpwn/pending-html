<?php

libxml_use_internal_errors(true);

$lista = json_decode(
    file_get_contents(__DIR__ . '/lista.json'),
    true
);

if (!is_array($lista)) {
    die('Hibás lista.json');
}


// meglévő epc betöltése
$epcFile = __DIR__ . '/epc.json';

if (file_exists($epcFile)) {

    $epc = json_decode(
        file_get_contents($epcFile),
        true
    );

    if (!is_array($epc)) {
        $epc = [];
    }

} else {

    $epc = [];

}


// meglévő ID-k kigyűjtése
$existing = [];

foreach ($epc as $oldItem) {

    if (isset($oldItem['id'])) {

        $existing[trim((string)$oldItem['id'])] = true;

    }

}
echo "EPC-ben lévő ID-k száma: " . count($existing) . "<br>";

// hiányzó ID-k kigyűjtése
$hianyzoLista = [];

foreach ($lista as $item) {

    $id = trim((string)$item['id']);

    if (!isset($existing[$id])) {

        $hianyzoLista[] = $item;

    }

}
echo "Hiányzó ID-k száma: " . count($hianyzoLista) . "<br>";

echo "<pre>";
foreach ($hianyzoLista as $hianyzo) {
    echo $hianyzo['id'] . "\n";
}
echo "</pre>";

echo "Új letöltések száma: " . count($hianyzoLista) . "<br><br>";

echo "Indul az import ciklus...<br>";
flush();
// csak a hiányzó elemeket dolgozzuk fel
foreach ($hianyzoLista as $index => $item) {

echo "Ciklus elem: ".$item['id']."<br>";
flush();
    $id = trim((string)$item['id']);


    // első letöltés előtt nincs várakozás
    if ($index > 0) {

        echo "Várakozás 60 másodpercig...<br>";

        flush();

        sleep(60);

    }


    echo "Import: " . $id . "<br>";


    $html = file_get_contents($item['url']);


    if ($html === false || strlen($html) == 0) {

        echo "Nem tölthető le: ".$id."<br><br>";

        continue;

    }


preg_match_all(
    '/<tr\b[^>]*data-part_no=.*?<\/tr>/is',
    $html,
    $matches
);
    
    


    $parts = [];


foreach ($matches[0] as $row) {

    preg_match_all(
        '/<td\b[^>]*>(.*?)<\/td>/is',
        $row,
        $cells
    );

    if (!isset($cells[1][2])) {
        continue;
    }


    // cikkszám keresése kizárólag az <em> tagből
    $partNumber = '';

    if (preg_match('/<em>\s*(.*?)\s*<\/em>/is', $cells[1][1], $partMatch)) {

        $partNumber = trim($partMatch[1]);

    } else {

        // ha nincs em tag (ritka eset)
        $partNumber = trim(strip_tags($cells[1][1]));

    }


    $parts[] = [

        'ref' => trim(strip_tags($cells[1][0])),

        'part_number' => $partNumber,

        'name' => trim(strip_tags($cells[1][2])),

        'qty' => ''

    ];

}


    $epc[] = [

        'id' => $id,

        'title' => $item['title'],

        'parts' => $parts

    ];


    // mentés minden egyes sikeres import után
    file_put_contents(
        $epcFile,
        json_encode(
            $epc,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        )
    );


    echo "Mentve: ".count($parts)." alkatrész<br><br>";

}


echo "Kész!";
