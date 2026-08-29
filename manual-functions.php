<?php

function getManualListFile(string $type, int $year, string $book = 'manual'): string
{
    if (!in_array($book, ['manual', 'body'], true)) {
        $book = 'manual';
    }

    return $_SERVER['DOCUMENT_ROOT']
        . '/data/manual/honda/accord/series_7/'
        . $type
        . '/'
        . $year
        . '/'
        . ($book === 'body'
            ? 'body-list.json'
            : 'manual-list.json'
        );
}
