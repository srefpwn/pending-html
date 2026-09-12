<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

$file = trim($_SERVER['PATH_INFO'] ?? '', '/');

if (
    $file === '' ||
    basename($file) !== $file ||
    !preg_match('/^[A-Za-z0-9_-]+$/', $file)
) {
    http_response_code(400);
    exit('Érvénytelen fájlazonosító.');
}

$contentFile =
    $_SERVER['DOCUMENT_ROOT']
    . '/data/manual/honda/accord/series_7/html/'
    . $file
    . '-content.html';

if (!is_file($contentFile)) {
    http_response_code(404);
    exit('A kért fájl nem található.');
}

$content = file_get_contents($contentFile);

if ($content === false) {
    http_response_code(500);
    exit('A fájl nem olvasható.');
}

header('Content-Type: text/html; charset=UTF-8');

echo $content;
