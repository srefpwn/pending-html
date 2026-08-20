<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';



$type = strtolower(
    trim((string)($_GET['type'] ?? 'cn1'))
);

$year = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT
);



if (
    $type === '' ||
    $year === false ||
    $year === null ||
    $year <= 0
) {
    exit('Érvénytelen típus vagy évjárat.');
}


$sourceDir =
    __DIR__
    . '/html-src';



$jsDir =
    __DIR__
    . '/js';



$htmlTargetDir =
    __DIR__
    . '/html';



$jsonTargetDir =
    __DIR__
    . '/data/'
    . $type
    . '/'
    . $year
    . '/pages';



if (!is_dir($sourceDir)) {

    exit(
        'A forrásmappa nem található: '
        . htmlspecialchars($sourceDir)
    );
}


if (!is_dir($jsDir)) {

    exit(
        'A JS mappa nem található: '
        . htmlspecialchars($jsDir)
    );
}


if (!is_dir($htmlTargetDir)) {

    if (!mkdir($htmlTargetDir, 0775, true)) {

        exit(
            'A HTML célmappa létrehozása sikertelen.'
        );
    }
}


if (!is_dir($jsonTargetDir)) {

    if (!mkdir($jsonTargetDir, 0775, true)) {

        exit(
            'A JSON célmappa létrehozása sikertelen.'
        );
    }
}



$files = array_merge(
    glob($sourceDir . '/*.html') ?: [],
    glob($sourceDir . '/*.htm') ?: []
);


if (count($files) === 0) {

    exit(
        'Nem található HTML fájl a html-src mappában.'
    );
}


function decodeJsString(
    string $value,
    string $quote
): string {


    $value = str_replace(
        '\\' . $quote,
        $quote,
        $value
    );




    $value = str_replace(
        '\"',
        '"',
        $value
    );

    $value = str_replace(
        "\\'",
        "'",
        $value
    );



    $value = str_replace(
        '\\n',
        "\n",
        $value
    );

    $value = str_replace(
        '\\r',
        "\r",
        $value
    );

    $value = str_replace(
        '\\t',
        "\t",
        $value
    );


    $value = str_replace(
        '\\\\',
        '\\',
        $value
    );


    return $value;
}


function convertImagePaths(
    string $html
): string {

    return preg_replace_callback(
        '#(<(?:img\b[^>]*|input\b[^>]*\btype\s*=\s*["\']image["\'][^>]*)\bsrc\s*=\s*["\'])([^"\']+)(["\'][^>]*>)#i',
        function ($match) {

            $src =
                trim($match[2]);

            $path =
                parse_url(
                    $src,
                    PHP_URL_PATH
                );

            if (
                $path === false ||
                $path === null
            ) {
                return $match[0];
            }

            $filename =
                basename($path);

            $filename =
                preg_replace(
                    '/\.png$/i',
                    '.PNG',
                    $filename
                );

            if ($filename === '') {
                return $match[0];
            }

            return
                $match[1]
                . '/manual/images/'
                . htmlspecialchars(
                    $filename,
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
                . $match[3];
        },
        $html
    );
}


function convertManualLinks(
    string $html,
    string $type,
    int $year,
    ?int $carId = null
): string {


    $html = preg_replace_callback(
        '#javascript\s*:\s*CtsProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\s*\)\s*;?#i',
        function ($match) use (
            $type,
            $year,
            $carId
        ) {

            $targetId =
                trim($match[2]);

            $url =
                '/manual/view.php'
                . '?type='
                . rawurlencode($type)
                . '&year='
                . rawurlencode((string)$year)
                . '&id='
                . rawurlencode($targetId);

            if ($carId !== null) {

                $url .=
                    '&car='
                    . rawurlencode(
                        (string)$carId
                    );
            }

            return $url;
        },
        $html
    );


    $html = preg_replace_callback(
        '#javascript\s*:\s*PrtProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]*)[\'"])?\s*\)\s*;?#i',
        function ($match) use (
            $type,
            $year,
            $carId
        ) {

            $targetId =
                trim($match[2]);

            $url =
                '/manual/view.php'
                . '?type='
                . rawurlencode($type)
                . '&year='
                . rawurlencode((string)$year)
                . '&id='
                . rawurlencode($targetId);

            if ($carId !== null) {

                $url .=
                    '&car='
                    . rawurlencode(
                        (string)$carId
                    );
            }

            return $url;
        },
        $html
    );


    return $html;
}


function isHondaWiringDiagram(
    string $html
): bool {

    return preg_match(
        '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>#i',
        $html
    ) === 1;
}


function removeHondaWiringButton(
    string $html
): string {


    $html = preg_replace(
        '#'
        . '<br\s*/?>\s*'
        . '<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>'
        . '\s*<br\s+clear\s*=\s*["\']left["\']\s*/?>'
        . '#is',
        '',
        $html
    );


    $html = preg_replace(
        '#'
        . '<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>'
        . '\s*<br\s+clear\s*=\s*["\']left["\']\s*/?>'
        . '#is',
        '',
        $html
    );


    $html = preg_replace(
        '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>#is',
        '',
        $html
    );

    return $html;
}


function prepareHondaWiringDiagram(
    string $html
): string {


    $html =
        removeHondaWiringButton(
            $html
        );


    $title = '';

    if (
        preg_match(
            '#<div\b[^>]*class\s*=\s*["\'][^"\']*\btop_title\b[^"\']*["\'][^>]*>(.*?)</div>#is',
            $html,
            $titleMatch
        )
    ) {

        $title =
            $titleMatch[0];


        $html =
            preg_replace(
                '#<div\b[^>]*class\s*=\s*["\'][^"\']*\btop_title\b[^"\']*["\'][^>]*>.*?</div>#is',
                '',
                $html,
                1
            );
    }



    $html = preg_replace(
        '#<br\s*/?>\s*#i',
        '',
        $html
    );


    $box =
        '<div class="manual-wiring-box">'
        . "\n"
        . '    <div class="manual-wiring-canvas">'
        . "\n"
        . $html
        . "\n"
        . '    </div>'
        . "\n"
        . '</div>';


    $css = <<<CSS
<style>

.manual-wiring-box {
    display: block;

    width: 100%;
    max-width: 100%;

    height: auto;

    overflow: auto;

    margin: 0px 0 0 0;
    padding: 0;

    box-sizing: border-box;

    border: 1px solid #cccccc;

    background: #cccccc;

    position: relative;
}

.manual-wiring-canvas {
    display: block;

    position: relative;

    width: max-content;
    min-width: 100%;

    height: max-content;

    margin: 0;
    padding: 0;

    line-height: normal;

    box-sizing: border-box;
}

.manual-wiring-canvas img {
    max-width: none;
}

</style>
CSS;



    return
        $css
        . "\n"
        . $title
        . "\n"
        . $box;
}


function isManualWiringDiagram(
    string $html
): bool {

    return
        stripos(
            $html,
            'IMAGE_DISPLAY_RD.PNG'
        ) !== false;
}


function convertManualWiringDiagram(
    string $html,
    string $type,
    int $year,
    string $jsDir
): string {



    if (
        stripos(
            $html,
            'IMAGE_DISPLAY_RD.PNG'
        ) === false
    ) {

        return $html;
    }


    if (
        !preg_match(
            '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>#is',
            $html,
            $buttonMatch
        )
    ) {

        return $html;
    }


    $buttonHtml =
        $buttonMatch[0];


    if (
        !preg_match(
            '#\bonClick\s*=\s*["\']([^"\']+)["\']#i',
            $buttonHtml,
            $urlMatch
        )
    ) {

        return $html;
    }


    $diagramUrl =
        trim(
            $urlMatch[1]
        );


    $query =
        parse_url(
            $diagramUrl,
            PHP_URL_QUERY
        );


    if (
        $query === false ||
        $query === null
    ) {

        return $html;
    }


    parse_str(
        $query,
        $params
    );


    $diagramId =
        trim(
            (string)($params['id'] ?? '')
        );


    if (
        $diagramId === ''
    ) {

        return $html;
    }


    $diagramFile = '';


    foreach (
        ['.html', '.htm'] as $extension
    ) {

        $candidate =
            __DIR__
            . '/html-src/'
            . $diagramId
            . $extension;


        if (
            is_file($candidate)
        ) {

            $diagramFile =
                $candidate;

            break;
        }
    }


    if (
        $diagramFile === ''
    ) {

        return $html;
    }



    $diagramHtml =
        file_get_contents(
            $diagramFile
        );


    if (
        $diagramHtml === false ||
        trim($diagramHtml) === ''
    ) {

        return $html;
    }

$offset = 20;

$diagramHtml = preg_replace_callback(
    '/(name=\\?"PrtPId\\?"[^>]*style=\\?"[^"]*?top:)(-?\d+(?:\.\d+)?)(px)/i',
    function ($match) use ($offset) {

        $top = (float)$match[2] - $offset;

        return
            $match[1]
            . $top
            . $match[3];
    },
    $diagramHtml
);

    if (
        !preg_match(
            '#<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+\.js(?:\?[^"\']*)?)["\'][^>]*>\s*</script>#is',
            $diagramHtml,
            $scriptMatch
        )
    ) {

        return $html;
    }


    $scriptSrc =
        trim(
            $scriptMatch[1]
        );


    $scriptPath =
        parse_url(
            $scriptSrc,
            PHP_URL_PATH
        );


    if (
        $scriptPath === false ||
        $scriptPath === null
    ) {

        return $html;
    }


    $scriptFilename =
        basename(
            $scriptPath
        );


    if (
        $scriptFilename === ''
    ) {

        return $html;
    }



    $jsFile =
        rtrim(
            $jsDir,
            '/\\'
        )
        . '/'
        . $scriptFilename;


    if (
        !is_file($jsFile)
    ) {

        return $html;
    }


    $js =
        file_get_contents(
            $jsFile
        );


    if (
        $js === false ||
        trim($js) === ''
    ) {

        return $html;
    }



    $js =
        str_replace(
            '../img/',
            '/manual/images/',
            $js
        );



    if (
        preg_match(
            '#<body\b[^>]*>(.*?)</body>#is',
            $diagramHtml,
            $bodyMatch
        )
    ) {

        $diagramBody =
            $bodyMatch[1];

    } else {

        $diagramBody =
            $diagramHtml;
    }



    $diagramBody =
        preg_replace(
            '#<script\b[^>]*>.*?</script>#is',
            '',
            $diagramBody
        );



    $diagramBody =
        preg_replace(
            '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>#is',
            '',
            $diagramBody,
            1
        );

$html =
    preg_replace(
        '#<v:group\b[^>]*>.*?</v:group>#is',
        '',
        $html
    );
    $iframeHtml =
        '<!DOCTYPE html>'
        . '<html lang="hu" xmlns:v="urn:schemas-microsoft-com:vml">'
        . '<head>'
        . '<meta charset="utf-8">'

        /*
         * Honda VML
         */
        . '<style>'
        . 'v\\:*{behavior:url(#default#VML);}'
        . '.drag{position:relative;cursor:hand;}'. '.drag{position:relative;cursor:hand;margin:0;padding:0;}'
                . 'html,body{'
        . 'margin:0;'
        . 'padding:0;'
        . 'background:#ffffff;'
        . '}'
        . '</style>'

        . '</head>'

        . '<body>'

        . $diagramBody

 

        . '<script>'
        . $js
        . '</script>'

        . '</body>'
        . '</html>';


    $iframeSrcdoc =
        htmlspecialchars(
            $iframeHtml,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );


    $diagramBox =
        '<div'
        . ' class="manual-wiring-diagram-box"'
        . ' style="'
        . 'display:block;'
        . 'width:100%;'
        . 'max-width:100%;'
        . 'height:80vh;'
        . 'overflow:hidden;'
        . 'box-sizing:border-box;'
        . 'border:1px solid #cccccc;'
        . 'background:#ffffff;'
        . 'margin:15px 0;'
        . 'padding:0;'
        . '">'
        
        . '<iframe'
        . ' srcdoc="' . $iframeSrcdoc . '"'
        . ' style="'
        . 'display:block;'
        . 'width:100%;'
        . 'height:100%;'
        . 'border:0;'
        . 'margin:0;'
        . 'padding:0;'
        . 'background:#ffffff;'
        . '"'
        . ' frameborder="0"'
        . ' scrolling="auto"'
        . '>'
        . '</iframe>'

        . '</div>';

    $html =
        preg_replace(
            '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG["\'][^>]*>#is',
            '',
            $html,
            1
        );



    return
        $diagramBox
        . $html;
}


function getHondaZoomCss(): string
{
    return <<<CSS
<style>

.honda-zoom {
    position: relative;

    width: 100%;
    height: auto;

    overflow: hidden;

    margin: 0;
    padding: 0;

    line-height: 0;
}

/*
 * ============================================================
 * FIX HONDA KOORDINÁTARENDSZER
 * ============================================================
 *
 * Az eredeti Honda koordináták mindig ebben
 * a 475 × 320 px rendszerben maradnak.
 *
 */

.honda-zoom-inner {
    position: absolute;

    left: 0;
    top: 0;

    transform-origin: top left;

    transform: scale(var(--honda-scale, 1));
}


/*
 * ============================================================
 * KÉP
 * ============================================================
 */

.honda-zoom-image {
    display: block;

    max-width: none;

    margin: 0;
    padding: 0;

    border: 0;

    object-fit: fill;
}

/*
 * ============================================================
 * FELIRATOK
 * ============================================================
 */

.honda-zoom-label {
    position: absolute;

    font-family: Arial, sans-serif;
    font-size: 9.20pt;
    font-weight: normal;

    line-height: 1.15;

    color: #000000;

    white-space: nowrap;

    pointer-events: auto;

    z-index: 2;
}


.honda-zoom-label b {
    font-weight: bold;
}


.honda-zoom-label a {
    pointer-events: auto;
    color: inherit;
}

</style>
CSS;
}
function getHondaZoomJs(): string
{
    return <<<JS
<script>

(function () {

    function resizeHondaZoom() {

        document
            .querySelectorAll('.honda-zoom')
            .forEach(function (zoom) {

                var inner =
                    zoom.querySelector('.honda-zoom-inner');

                if (!inner) {
                    return;
                }

                var width =
                    zoom.clientWidth;

                if (width <= 0) {
                    return;
                }

                var scale =
                    width / 475;

                zoom.style.setProperty(
                    '--honda-scale',
                    scale
                );

            });
    }


    /*
     * Első méretezés
     */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            resizeHondaZoom
        );

    } else {

        resizeHondaZoom();

    }


    /*
     * Ablak átméretezése
     */

    window.addEventListener(
        'resize',
        resizeHondaZoom
    );


    /*
     * Ha a konténer mérete változik
     * nem csak az ablaké.
     */

    if (
        typeof ResizeObserver !== 'undefined'
    ) {

        var observer =
            new ResizeObserver(
                resizeHondaZoom
            );

        document
            .querySelectorAll('.honda-zoom')
            .forEach(function (zoom) {

                observer.observe(zoom);

            });

    }

})();

</script>
JS;
}

function convertHondaZoom(
    string $html
): string {



    $pattern =
        '#<v:group\b([^>]*)>(.*?)</v:group>#is';


    $html =
        preg_replace_callback(
            $pattern,
            function ($match) {

                $groupAttributes =
                    $match[1];

                $groupContent =
                    $match[2];


 

                $width = 0;
                $height = 0;


                if (
                    preg_match(
                        '#width\s*:\s*([0-9.]+)\s*px#i',
                        $groupAttributes,
                        $m
                    )
                ) {

                    $width =
                        (float)$m[1];
                }


                if (
                    preg_match(
                        '#height\s*:\s*([0-9.]+)\s*px#i',
                        $groupAttributes,
                        $m
                    )
                ) {

                    $height =
                        (float)$m[1];
                }



                if (
                    $width <= 0 ||
                    $height <= 0
                ) {

                    return $match[0];
                }


                $imageHtml = '';


                if (
                    preg_match(
                        '#<img\b([^>]*)>#i',
                        $groupContent,
                        $imageMatch
                    )
                ) {

                    $imageAttributes =
                        $imageMatch[1];



                    $imageAttributes =
                        preg_replace(
                            '#\s+style\s*=\s*["\'][^"\']*["\']#i',
                            '',
                            $imageAttributes
                        );



                    if (
                        preg_match(
                            '#\bclass\s*=\s*["\']([^"\']*)["\']#i',
                            $imageAttributes
                        )
                    ) {

                        $imageAttributes =
                            preg_replace(
                                '#\bclass\s*=\s*["\']([^"\']*)["\']#i',
                                'class="$1 honda-zoom-image"',
                                $imageAttributes
                            );

                    } else {

                        $imageAttributes .=
                            ' class="honda-zoom-image"';
                    }


$imageHtml =
    '<img'
    . $imageAttributes
    . ' style="'
    . 'width:' . $width . 'px;'
    . 'height:' . $height . 'px;'
    . '">';
                }



                $labels = '';



                preg_match_all(
                    '#<p\b([^>]*)>(.*?)</p>#is',
                    $groupContent,
                    $paragraphs,
                    PREG_SET_ORDER
                );


                foreach (
                    $paragraphs as $paragraph
                ) {

                    $attributes =
                        $paragraph[1];

                    $content =
                        $paragraph[2];



                    if (
                        !preg_match(
                            '#left\s*:\s*([0-9.]+)\s*px#i',
                            $attributes,
                            $leftMatch
                        )
                    ) {

                        continue;
                    }



                    if (
                        !preg_match(
                            '#top\s*:\s*([0-9.]+)\s*px#i',
                            $attributes,
                            $topMatch
                        )
                    ) {

                        continue;
                    }



                    $left =
                        (float)$leftMatch[1];

                    $top =
                        (float)$topMatch[1];


                    $content =
                        preg_replace(
                            '#</?nobr\b[^>]*>#i',
                            '',
                            $content
                        );


                    $content =
                        preg_replace(
                            '#<br\s*/?>#i',
                            ' ',
                            $content
                        );


                    $content =
                        trim($content);


                    $labels .=
                        '<div'
                        . ' class="honda-zoom-label"'
                        . ' style="'
                        . 'left:'
                        . number_format(
                            $left,
                            2,
                            '.',
                            ''
                        )
                        . 'px;'
                        . 'top:'
                        . number_format(
                            $top,
                            2,
                            '.',
                            ''
                        )
                        . 'px;'
                        . '">'
                        . $content
                        . '</div>';
                }



return
    '<div class="honda-zoom"'
    . ' style="'
    . '--honda-width:' . $width . 'px;'
    . '--honda-height:' . $height . 'px;'
    . 'aspect-ratio:' . $width . ' / ' . $height . ';'
    . '">'
    . '<div class="honda-zoom-inner"'
    . ' style="'
    . 'width:' . $width . 'px;'
    . 'height:' . $height . 'px;'
    . '">'
    . $imageHtml
    . $labels
    . '</div>'
    . '</div>';
            },
            $html
        );



    return $html;
}


function convertVmlToHtml(
    string $html
): string {



    $html = preg_replace(
        '#<v:group\b([^>]*)>#i',
        '<div$1>',
        $html
    );


    $html = preg_replace(
        '#</v:group>#i',
        '</div>',
        $html
    );



    $html = preg_replace(
        '#<v:rect\b([^>]*)>#i',
        '<div$1>',
        $html
    );


    $html = preg_replace(
        '#</v:rect>#i',
        '</div>',
        $html
    );



    $html = preg_replace(
        '#\s+xmlns:v\s*=\s*["\'][^"\']*["\']#i',
        '',
        $html
    );



    $html = preg_replace(
        '#\s+coordsize\s*=\s*["\'][^"\']*["\']#i',
        '',
        $html
    );


    return $html;
}


function removeHondaPrintBlocks(
    string $html
): string {

    $pattern =
        '#<span\b[^>]*\bid\s*=\s*["\']imgPrtId["\'][^>]*>#i';

    while (
        preg_match(
            $pattern,
            $html,
            $match,
            PREG_OFFSET_CAPTURE
        )
    ) {

        $start =
            $match[0][1];

        $openTag =
            $match[0][0];

        $pos =
            $start
            + strlen($openTag);

        $depth = 1;


        while (
            $depth > 0 &&
            preg_match(
                '#</?span\b[^>]*>#i',
                $html,
                $spanMatch,
                PREG_OFFSET_CAPTURE,
                $pos
            )
        ) {

            $tag =
                $spanMatch[0][0];

            $tagPos =
                $spanMatch[0][1];

            if (
                preg_match(
                    '#^<span\b#i',
                    $tag
                )
            ) {

                $depth++;

            } else {

                $depth--;
            }

            $pos =
                $tagPos
                + strlen($tag);
        }


        if ($depth === 0) {

            $html =
                substr($html, 0, $start)
                . substr($html, $pos);

        } else {



            break;
        }
    }

    return $html;
}

function moveManualGraphBelowText(
    string $html
): string {
    $pattern =
        '#<table\b([^>]*\bclass\s*=\s*["\'][^"\']*\bViewer\b[^"\']*["\'][^>]*)>'
        . '(.*?)'
        . '</table>#is';

    return preg_replace_callback(
        $pattern,
        function ($match) {

            $tableAttributes =
                $match[1];

            $tableContent =
                $match[2];

            if (
                stripos(
                    $tableContent,
                    'id="graphTd"'
                ) === false &&
                stripos(
                    $tableContent,
                    "id='graphTd'"
                ) === false
            ) {
                return $match[0];
            }

            if (
                !preg_match(
                    '#<td\b([^>]*\bid\s*=\s*["\']textTd["\'][^>]*)>(.*?)</td>#is',
                    $tableContent,
                    $textMatch
                )
            ) {
                return $match[0];
            }

            if (
                !preg_match(
                    '#<td\b([^>]*\bid\s*=\s*["\']graphTd["\'][^>]*)>(.*?)</td>#is',
                    $tableContent,
                    $graphMatch
                )
            ) {
                return $match[0];
            }

            $textContent =
                $textMatch[2];

            $graphContent =
                $graphMatch[2];

            /*
             * Csak a Honda Zoom esetén tesszük
             * a grafikát új sorba.
             */
            if (
                strpos(
                    $graphContent,
                    'class="honda-zoom"'
                ) === false
            ) {
                return $match[0];
            }

            return
                '<table'
                . $tableAttributes
                . '>'
                . "\n"

                . '<tr>'
                . "\n"
                . '<td'
                . ' class="manual-text-cell"'
                . ' style="width:100%;padding:0;vertical-align:top;"'
                . '>'
                . $textContent
                . '</td>'
                . "\n"
                . '</tr>'

                . "\n"

                . '<tr>'
                . "\n"
                . '<td'
                . ' class="manual-graph-cell"'
                . ' style="width:100%;padding:0;vertical-align:top;"'
                . '>'
                . $graphContent
                . '</td>'
                . "\n"
                . '</tr>'

                . "\n"

                . '</table>';
        },
        $html
    );
}

function keepManualGraphBesideText(
    string $html
): string {


    $pattern =
        '#<table\b([^>]*\bclass\s*=\s*["\'][^"\']*\bViewer\b[^"\']*["\'][^>]*)>'
        . '(.*?)'
        . '</table>#is';


    return preg_replace_callback(
        $pattern,
        function ($match) {

            $tableAttributes =
                $match[1];

            $tableContent =
                $match[2];


            if (
                stripos(
                    $tableContent,
                    'id="graphTd"'
                ) === false &&
                stripos(
                    $tableContent,
                    "id='graphTd'"
                ) === false
            ) {

                return $match[0];
            }



            if (
                !preg_match(
                    '#<td\b([^>]*\bid\s*=\s*["\']textTd["\'][^>]*)>(.*?)</td>#is',
                    $tableContent,
                    $textMatch
                )
            ) {

                return $match[0];
            }


            if (
                !preg_match(
                    '#<td\b([^>]*\bid\s*=\s*["\']graphTd["\'][^>]*)>(.*?)</td>#is',
                    $tableContent,
                    $graphMatch
                )
            ) {

                return $match[0];
            }


            $textAttributes =
                $textMatch[1];

            $textContent =
                $textMatch[2];

            $graphAttributes =
                $graphMatch[1];

            $graphContent =
                $graphMatch[2];


            if (
                strpos(
                    $graphContent,
                    'class="honda-zoom"'
                ) !== false
            ) {

                return $match[0];
            }


            return
                '<table'
                . $tableAttributes
                . '>'
                . "\n"
                . '<tr>'
                . "\n"


                . '<td'
                . ' class="manual-text-cell"'
                . ' style="'
                . 'width:80%;'
                . 'padding:0;'
                . 'vertical-align:top;'
                . '">'
                . $textContent
                . '</td>'
                . "\n"


                . '<td'
                . ' class="manual-graph-cell"'
                . ' style="'
                . 'width:20%;'
                . 'padding:0;'
                . 'vertical-align:top;'
                . '">'
                . $graphContent
                . '</td>'
                . "\n"

                . '</tr>'
                . "\n"
                . '</table>';
        },
        $html
    );
}


function processJsFile(
    string $jsFile,
    string $type,
    int $year,
    bool $skipZoom = false
): string {

    if (!is_file($jsFile)) {

        return '';
    }


    $js =
        file_get_contents($jsFile);


    if ($js === false) {

        return '';
    }


    $html = '';


    $length =
        strlen($js);

    $pos = 0;


    while ($pos < $length) {


        $matchLength = 0;


        if (
            substr(
                $js,
                $pos,
                15
            ) === 'document.write('
        ) {

            $matchLength = 15;

        } elseif (
            substr(
                $js,
                $pos,
                6
            ) === 'write('
        ) {

            $matchLength = 6;
        }



        if ($matchLength === 0) {

            $pos++;

            continue;
        }


        $i =
            $pos + $matchLength;


        while (
            $i < $length &&
            preg_match(
                '/\s/',
                $js[$i]
            )
        ) {

            $i++;
        }



        if (
            $i >= $length ||
            (
                $js[$i] !== '"' &&
                $js[$i] !== "'"
            )
        ) {

            $pos += $matchLength;

            continue;
        }


        $quote =
            $js[$i];

        $i++;


        $content = '';

        $escaped = false;


        while ($i < $length) {

            $char =
                $js[$i];


            if ($escaped) {

                $content .=
                    '\\'
                    . $char;

                $escaped = false;

                $i++;

                continue;
            }


            if ($char === '\\') {

                $escaped = true;

                $i++;

                continue;
            }


            if ($char === $quote) {

                break;
            }


            $content .=
                $char;

            $i++;
        }


        if (
            $i >= $length ||
            $js[$i] !== $quote
        ) {

            $pos += $matchLength;

            continue;
        }



        $html .=
            decodeJsString(
                $content,
                $quote
            );



        $pos =
            $i + 1;
    }



    if ($html === '') {

        return '';
    }



    $html =
        convertImagePaths(
            $html
        );



    $html =
        convertManualLinks(
            $html,
            $type,
            $year
        );


 

$html =
    removeHondaPrintBlocks(
        $html
    );


if (!$skipZoom) {

    $html =
        convertHondaZoom(
            $html
        );

    $html =
        convertVmlToHtml(
            $html
        );
}

    return $html;
}



function convertWiringDiagram(
    string $fragment,
    string $type,
    int $year,
    string $jsDir,
    string $sourceDir
): string {



    if (
        !preg_match(
            '#<img\b(?=[^>]*\bsrc\s*=\s*["\']/manual/images/IMAGE_DISPLAY_RD\.PNG["\'])[^>]*>#i',
            $fragment,
            $buttonMatch
        )
    ) {

        return $fragment;
    }


    $button =
        $buttonMatch[0];



    if (
        !preg_match(
            '#\bonClick\s*=\s*["\']([^"\']+)["\']#i',
            $button,
            $clickMatch
        )
    ) {

        return $fragment;
    }


    $targetUrl =
        trim($clickMatch[1]);



    if (
        !preg_match(
            '#[?&]id=([^&"\']+)#i',
            $targetUrl,
            $idMatch
        )
    ) {

        return $fragment;
    }


    $targetId =
        rawurldecode(
            $idMatch[1]
        );



    $targetFile =
        $sourceDir
        . '/'
        . $targetId
        . '.html';


    if (!is_file($targetFile)) {

        return $fragment;
    }


    $targetHtml =
        file_get_contents($targetFile);


    if ($targetHtml === false) {

        return $fragment;
    }



    $diagramFragment =
        prepareHtmlFragment(
            $targetHtml,
            $type,
            $year,
            $jsDir,
            true
        );


    if ($diagramFragment === '') {

        return $fragment;
    }


    $fragment =
        preg_replace_callback(
            '#<td\b([^>]*)>(.*?)</td>#is',
            function ($match) {

                $content =
                    $match[2];

                if (
                    stripos(
                        $content,
                        'IMAGE_DISPLAY_RD.PNG'
                    ) !== false
                ) {

                    return '';
                }

                return $match[0];
            },
            $fragment
        );


$diagramBox =
    '<div'
    . ' class="manual-wiring-diagram-box"'
    . ' style="'
    . 'display:block;'
    . 'width:100%;'
    . 'max-width:100%;'
    . 'height:80vh;'
    . 'overflow:auto;'
    . 'box-sizing:border-box;'
    . 'border:1px solid #cccccc;'
    . 'background:#ffffff;'
    . 'margin:15px 0;'
    . 'padding:0;'
    . 'font-family:Arial,sans-serif;'
    . 'font-size:initial;'
    . 'font-weight:normal;'
    . 'line-height:normal;'
    . 'color:#000000;'
    . '"'
    . '>'
    
. '<style>'

. '.manual-wiring-diagram-box img {'
. 'max-width: none !important;'
. 'max-height: none !important;'
. 'width: auto;'
. 'height: auto;'
. '}'

. '.manual-wiring-diagram-box table {'
. 'max-width: none !important;'
. '}'

. '.manual-wiring-diagram-box td {'
. 'padding: 0 !important;'
. '}'

. '</style>'

    . '<script>'
    . $js
    . '</script>'

    . '</div>';



    $fragment =
        preg_replace(
            '#<td\b(?=[^>]*\bid\s*=\s*["\']graphTd["\'])[^>]*>.*?</td>#is',
            '',
            $fragment
        );


    $fragment =
        preg_replace(
            '#<img\b(?=[^>]*\bsrc\s*=\s*["\']/manual/images/IMAGE_DISPLAY_RD\.PNG["\'])[^>]*>#i',
            '',
            $fragment
        );



    $fragment .=
        "\n"
        . $diagramBox
        . "\n";


    return $fragment;
}

function buildHondaIframeDocument(
    string $html,
    string $type,
    int $year,
    string $jsDir,
    bool $isWiringDiagram
): string {

    /*
     * ------------------------------------------------------------
     * 1. Az eredeti HTML teljes dokumentumának előkészítése
     * ------------------------------------------------------------
     */

    $documentHtml = $html;


    /*
     * ------------------------------------------------------------
     * 2. A külső Honda JS fájlok beillesztése
     *
     * Az eredeti <script src="..."> helyére a JS tényleges
     * tartalmát tesszük inline <script>-ként.
     * ------------------------------------------------------------
     */

    $documentHtml =
        preg_replace_callback(
            '#<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+\.js(?:\?[^"\']*)?)["\'][^>]*>\s*</script>#is',
            function ($match) use (
                $jsDir
            ) {

                $scriptSrc =
                    trim($match[1]);

                $scriptPath =
                    parse_url(
                        $scriptSrc,
                        PHP_URL_PATH
                    );

                if (
                    $scriptPath === false ||
                    $scriptPath === null
                ) {
                    return '';
                }

                $scriptFilename =
                    basename(
                        $scriptPath
                    );

                if (
                    $scriptFilename === ''
                ) {
                    return '';
                }

                $jsFile =
                    rtrim(
                        $jsDir,
                        '/\\'
                    )
                    . '/'
                    . $scriptFilename;

                if (
                    !is_file($jsFile)
                ) {
                    return '';
                }

                $js =
                    file_get_contents(
                        $jsFile
                    );

                if (
                    $js === false ||
                    trim($js) === ''
                ) {
                    return '';
                }


                /*
                 * Honda képutak.
                 */
                $js =
                    str_replace(
                        '../img/',
                        '/manual/images/',
                        $js
                    );


                /*
                 * A JS-ben szereplő Honda linkek
                 * átalakítása.
                 */
                $js =
                    convertManualLinks(
                        $js,
                        $GLOBALS['type'] ?? '',
                        (int)($GLOBALS['year'] ?? 0)
                    );


                return
                    '<script>'
                    . "\n"
                    . $js
                    . "\n"
                    . '</script>';
            },
            $documentHtml
        );


    /*
     * ------------------------------------------------------------
     * 3. Képek URL-jeinek javítása
     * ------------------------------------------------------------
     */

    $documentHtml =
        convertImagePaths(
            $documentHtml
        );


    /*
     * ------------------------------------------------------------
     * 4. A normál HTML-ben lévő Honda linkek javítása
     * ------------------------------------------------------------
     */

    $documentHtml =
        convertManualLinks(
            $documentHtml,
            $type,
            $year
        );


    /*
     * ------------------------------------------------------------
     * 5. A fölösleges Honda kezelőfelület eltávolítása
     *
     * Nem a konkrét képnevekre támaszkodunk kizárólag.
     * A JS lefutása után is végrehajtjuk ugyanezt DOM-ból.
     * ------------------------------------------------------------
     */

    $cleanupJs = <<<JS
<script>
(function () {

    function removeHondaControls() {

        /*
         * Zoom toolbar.
         */
        var toolbar =
            document.getElementById('toolbar');

        if (toolbar) {
            toolbar.remove();
        }


        /*
         * groupbar, ha külön maradt.
         */
        var groupbar =
            document.getElementById('groupbar');

        if (groupbar) {
            groupbar.remove();
        }


        /*
         * Print / Back / Next / Cancel gombok.
         */
        document
            .querySelectorAll('input[type="image"]')
            .forEach(function (input) {

                var onclick =
                    input.getAttribute('onclick') || '';

                if (
                    /PrintFunc|BackFunc|NextFunc|CloseFunc/i.test(
                        onclick
                    )
                ) {

                    var parent =
                        input.parentElement;

                    /*
                     * Ha a gombok közös DIV-ben vannak,
                     * az egész vezérlőblokkot töröljük.
                     */
                    if (
                        parent &&
                        parent.tagName.toLowerCase() === 'div'
                    ) {
                        parent.remove();
                    } else {
                        input.remove();
                    }
                }
            });


        /*
         * Biztonsági eltávolítás a gombképek alapján is.
         */
        document
            .querySelectorAll('img')
            .forEach(function (img) {

                var src =
                    img.getAttribute('src') || '';

                if (
                    /PRINT_PREVIEW|GL_PREV|GL_NEXT|GL_CANCEL/i.test(
                        src
                    )
                ) {

                    var parent =
                        img.parentElement;

                    if (
                        parent &&
                        parent.tagName.toLowerCase() === 'div'
                    ) {
                        parent.remove();
                    } else {
                        img.remove();
                    }
                }
            });
    }


    /*
     * A Honda document.write() után futunk.
     */
    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            removeHondaControls
        );

    } else {

        removeHondaControls();

    }


    /*
     * Biztonsági második futás,
     * mert a Honda régi JS-e esetenként
     * később írhatja ki az elemeket.
     */
    setTimeout(
        removeHondaControls,
        50
    );

    setTimeout(
        removeHondaControls,
        250
    );

})();
</script>
JS;


    /*
     * ------------------------------------------------------------
     * 6. Dinamikus iframe magasság
     *
     * Normál oldalon a teljes tartalom magasságát küldjük
     * a szülő oldalnak.
     * ------------------------------------------------------------
     */

    $resizeJs = <<<JS
<script>
(function () {

    function sendHeight() {

        var body =
            document.body;

        var html =
            document.documentElement;

        if (!body || !html) {
            return;
        }

        var height =
            Math.max(
                body.scrollHeight,
                body.offsetHeight,
                html.scrollHeight,
                html.offsetHeight,
                html.clientHeight
            );

        window.parent.postMessage(
            {
                type: 'honda-iframe-height',
                height: height
            },
            '*'
        );
    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            sendHeight
        );

    } else {

        sendHeight();

    }


    window.addEventListener(
        'load',
        sendHeight
    );


    if (
        typeof ResizeObserver !== 'undefined'
    ) {

        var observer =
            new ResizeObserver(
                sendHeight
            );

        observer.observe(
            document.documentElement
        );

        if (document.body) {
            observer.observe(
                document.body
            );
        }
    }


    setTimeout(
        sendHeight,
        100
    );

    setTimeout(
        sendHeight,
        500
    );

})();
</script>
JS;


    /*
     * ------------------------------------------------------------
     * 7. Saját iframe CSS
     * ------------------------------------------------------------
     */

    $iframeCss = <<<CSS
<style>

html,
body {
    margin: 0 !important;
    padding: 0 !important;
}

</style>
CSS;


    /*
     * ------------------------------------------------------------
     * 8. HEAD előkészítése
     * ------------------------------------------------------------
     */

    if (
        preg_match(
            '#<head\b[^>]*>(.*?)</head>#is',
            $documentHtml,
            $headMatch
        )
    ) {

        $head =
            $headMatch[1];

    } else {

        $head = '';

    }


    /*
     * A linkek alapértelmezett célja a teljes oldal.
     */
    $head =
        '<base target="_top">'
        . "\n"
        . $iframeCss
        . "\n"
        . $head;


    /*
     * A saját HEAD visszahelyezése.
     */
    if (
        preg_match(
            '#<head\b[^>]*>.*?</head>#is',
            $documentHtml
        )
    ) {

        $documentHtml =
            preg_replace(
                '#<head\b[^>]*>.*?</head>#is',
                '<head>'
                . "\n"
                . $head
                . "\n"
                . '</head>',
                $documentHtml,
                1
            );

    } else {

        $documentHtml =
            '<!DOCTYPE html>'
            . '<html lang="hu">'
            . '<head>'
            . "\n"
            . $head
            . "\n"
            . '</head>'
            . $documentHtml
            . '</html>';
    }


    /*
     * ------------------------------------------------------------
     * 9. A normál oldal / kapcsolási rajz különbsége
     * ------------------------------------------------------------
     */

    $modeJs =
        $isWiringDiagram
            ? ''
            : <<<JS
<script>
(function () {

    /*
     * Normál oldalnál a Honda tartalmat
     * a rendelkezésre álló szélességhez igazítjuk.
     *
     * A kapcsolási rajzhoz ezt NEM alkalmazzuk.
     */

    function fitHondaContent() {

        var body =
            document.body;

        if (!body) {
            return;
        }

        var viewportWidth =
            document.documentElement.clientWidth;

        if (viewportWidth <= 0) {
            return;
        }

        /*
         * Olyan Honda elemeket keresünk,
         * amelyek saját fix szélességet adnak.
         */
        var candidates =
            document.querySelectorAll(
                '[style*="width:"][style*="px"]'
            );

        var largestWidth = 0;

        candidates.forEach(
            function (element) {

                var style =
                    window.getComputedStyle(
                        element
                    );

                var width =
                    parseFloat(
                        style.width
                    );

                if (
                    width > largestWidth
                ) {
                    largestWidth = width;
                }
            }
        );


        if (
            largestWidth <= viewportWidth ||
            largestWidth <= 0
        ) {
            return;
        }


        /*
         * A teljes Honda tartalmat együtt
         * skálázzuk.
         */
        body.style.transformOrigin =
            'top left';

        body.style.transform =
            'scale('
            + (
                viewportWidth /
                largestWidth
            )
            + ')';

        body.style.width =
            (
                largestWidth
            )
            + 'px';

        sendHeight();
    }


    function sendHeight() {

        var body =
            document.body;

        if (!body) {
            return;
        }

        var rect =
            body.getBoundingClientRect();

        var height =
            rect.height;

        window.parent.postMessage(
            {
                type: 'honda-iframe-height',
                height: Math.ceil(height)
            },
            '*'
        );
    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            fitHondaContent
        );

    } else {

        fitHondaContent();

    }


    window.addEventListener(
        'resize',
        fitHondaContent
    );

})();
</script>
JS;


    /*
     * ------------------------------------------------------------
     * 10. A saját scriptjeinket a BODY végére tesszük.
     * ------------------------------------------------------------
     */

    $documentHtml =
        preg_replace(
            '#</body>#i',
            $cleanupJs
            . "\n"
            . $resizeJs
            . "\n"
            . $modeJs
            . "\n"
            . '</body>',
            $documentHtml,
            1
        );


    return $documentHtml;
}

/**
 * Honda oldal előkészítése iframe számára.
 *
 * A gyári HTML + JS tartalmat nem alakítjuk át.
 * Csak:
 * - a kép URL-eket javítjuk,
 * - a linkeket javítjuk,
 * - eltávolítjuk a gyári kezelőgombokat / toolbart,
 * - saját iframe környezetet adunk neki.
 *
 * Kapcsolási rajznál:
 * - eredeti munkaméret,
 * - görgethető iframe.
 *
 * Normál oldalnál:
 * - rendelkezésre álló szélességhez igazítás,
 * - dinamikus magasság.
 */
function buildHondaIframeDocument(
    string $html,
    string $type,
    int $year,
    string $jsDir,
    bool $isWiringDiagram
): string {

    $documentHtml = $html;


    /*
     * ------------------------------------------------------------
     * 1. KÜLSŐ JS FÁJLOK BEILLESZTÉSE
     * ------------------------------------------------------------
     *
     * A Honda eredeti JS-ei document.write() hívásokkal
     * állítják elő a diagram HTML-jét.
     *
     * Nem alakítjuk át a document.write() tartalmát.
     * Egyszerűen inline scriptként futtatjuk.
     */

    $documentHtml =
        preg_replace_callback(
            '#<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+\.js(?:\?[^"\']*)?)["\'][^>]*>\s*</script>#is',
            function ($match) use (
                $jsDir,
                $type,
                $year
            ) {

                $scriptSrc =
                    trim(
                        $match[1]
                    );

                $scriptPath =
                    parse_url(
                        $scriptSrc,
                        PHP_URL_PATH
                    );

                if (
                    $scriptPath === false ||
                    $scriptPath === null
                ) {
                    return '';
                }

                $scriptFilename =
                    basename(
                        $scriptPath
                    );

                if (
                    $scriptFilename === ''
                ) {
                    return '';
                }

                $jsFile =
                    rtrim(
                        $jsDir,
                        '/\\'
                    )
                    . DIRECTORY_SEPARATOR
                    . $scriptFilename;

                if (
                    !is_file(
                        $jsFile
                    )
                ) {
                    return '';
                }

                $js =
                    file_get_contents(
                        $jsFile
                    );

                if (
                    $js === false ||
                    trim($js) === ''
                ) {
                    return '';
                }


                /*
                 * ------------------------------------------------
                 * Honda képutak javítása a JS-ben.
                 * ------------------------------------------------
                 */

                $js =
                    str_replace(
                        '../img/',
                        '/manual/images/',
                        $js
                    );


                /*
                 * Ha a JS-ben Honda manual linkek vannak,
                 * azokat is a saját útvonalunkra igazítjuk.
                 */

                $js =
                    convertManualLinks(
                        $js,
                        $type,
                        $year
                    );


                return
                    '<script>'
                    . "\n"
                    . $js
                    . "\n"
                    . '</script>';
            },
            $documentHtml
        );


    /*
     * ------------------------------------------------------------
     * 2. HTML-BEN LÉVŐ KÉP URL-EK JAVÍTÁSA
     * ------------------------------------------------------------
     */

    $documentHtml =
        convertImagePaths(
            $documentHtml
        );


    /*
     * ------------------------------------------------------------
     * 3. HTML-BEN LÉVŐ MANUAL LINKEK JAVÍTÁSA
     * ------------------------------------------------------------
     */

    $documentHtml =
        convertManualLinks(
            $documentHtml,
            $type,
            $year
        );


    /*
     * ------------------------------------------------------------
     * 4. A GYÁRI KAPCSOLÓGOMBOK / TOOLBAR ELTÁVOLÍTÁSA
     * ------------------------------------------------------------
     *
     * A Honda többféle változatban használhatja ezeket,
     * ezért nem egyetlen konkrét HTML-sorra építünk.
     *
     * A document.write() által létrehozott elemeket a böngésző
     * fogja eltávolítani az iframe betöltése után.
     */

    $cleanupScript = <<<'JS'
<script>
(function () {

    function removeHondaControls() {

        /*
         * --------------------------------------------------------
         * Zoom toolbar
         * --------------------------------------------------------
         */

        var toolbar =
            document.getElementById('toolbar');

        if (toolbar) {
            toolbar.remove();
        }


        /*
         * --------------------------------------------------------
         * groupbar
         * --------------------------------------------------------
         */

        var groupbar =
            document.getElementById('groupbar');

        if (groupbar) {
            groupbar.remove();
        }


        /*
         * --------------------------------------------------------
         * Print / Back / Next / Cancel
         * --------------------------------------------------------
         */

        document
            .querySelectorAll(
                'input[type="image"]'
            )
            .forEach(
                function (input) {

                    var onclick =
                        input.getAttribute(
                            'onclick'
                        ) || '';

                    if (
                        /PrintFunc|BackFunc|NextFunc|CloseFunc/i.test(
                            onclick
                        )
                    ) {

                        var parent =
                            input.parentElement;

                        if (
                            parent &&
                            parent.tagName.toLowerCase() === 'div'
                        ) {
                            parent.remove();
                        } else {
                            input.remove();
                        }
                    }
                }
            );


        /*
         * --------------------------------------------------------
         * Biztonsági eltávolítás a gombképek alapján.
         * --------------------------------------------------------
         */

        document
            .querySelectorAll('img')
            .forEach(
                function (img) {

                    var src =
                        img.getAttribute(
                            'src'
                        ) || '';

                    if (
                        /PRINT_PREVIEW|GL_PREV|GL_NEXT|GL_CANCEL/i.test(
                            src
                        )
                    ) {

                        var parent =
                            img.parentElement;

                        if (
                            parent &&
                            parent.tagName.toLowerCase() === 'div'
                        ) {
                            parent.remove();
                        } else {
                            img.remove();
                        }
                    }
                }
            );
    }


    /*
     * Első futás.
     */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            removeHondaControls
        );

    } else {

        removeHondaControls();

    }


    /*
     * Második futás.
     *
     * A régi Honda JS-ek miatt biztonságból
     * később is lefuttatjuk.
     */

    setTimeout(
        removeHondaControls,
        50
    );

    setTimeout(
        removeHondaControls,
        250
    );

})();
</script>
JS;


    /*
     * ------------------------------------------------------------
     * 5. LINK CÉLJA
     * ------------------------------------------------------------
     *
     * Az iframe-en belüli normál <a href="..."> linkek
     * a teljes szülőoldalt nyissák meg.
     */

    $baseTag =
        '<base target="_top">';


    /*
     * ------------------------------------------------------------
     * 6. SAJÁT IFRAME CSS
     * ------------------------------------------------------------
     */

    $iframeCss = <<<'CSS'
<style>

html,
body {
    margin: 0 !important;
    padding: 0 !important;
    background: #ffffff;
}

</style>
CSS;


    /*
     * ------------------------------------------------------------
     * 7. HEAD FELÉPÍTÉSE
     * ------------------------------------------------------------
     */

    if (
        preg_match(
            '#<head\b[^>]*>(.*?)</head>#is',
            $documentHtml,
            $headMatch
        )
    ) {

        $originalHead =
            $headMatch[1];

    } else {

        $originalHead = '';

    }


    $newHead =
        $baseTag
        . "\n"
        . $iframeCss
        . "\n"
        . $originalHead;


    /*
     * Ha volt eredeti HEAD, lecseréljük a saját,
     * kiegészített HEAD-re.
     */

    if (
        preg_match(
            '#<head\b[^>]*>.*?</head>#is',
            $documentHtml
        )
    ) {

        $documentHtml =
            preg_replace(
                '#<head\b[^>]*>.*?</head>#is',
                '<head>'
                . "\n"
                . $newHead
                . "\n"
                . '</head>',
                $documentHtml,
                1
            );

    } else {

        /*
         * Ha nincs HEAD, létrehozunk egy teljes HTML dokumentumot.
         */

        $documentHtml =
            '<!DOCTYPE html>'
            . '<html lang="hu">'
            . '<head>'
            . "\n"
            . $newHead
            . "\n"
            . '</head>'
            . "\n"
            . $documentHtml
            . "\n"
            . '</html>';
    }


    /*
     * ------------------------------------------------------------
     * 8. DINAMIKUS MAGASSÁG
     * ------------------------------------------------------------
     *
     * Normál oldalnál a tényleges tartalommagasságot
     * elküldjük a szülőoldalnak.
     */

    $heightScript = <<<'JS'
<script>
(function () {

    function sendHondaHeight() {

        var body =
            document.body;

        var html =
            document.documentElement;

        if (!body || !html) {
            return;
        }

        var height =
            Math.max(
                body.scrollHeight,
                body.offsetHeight,
                html.scrollHeight,
                html.offsetHeight,
                html.clientHeight
            );

        window.parent.postMessage(
            {
                type: 'honda-iframe-height',
                height: Math.ceil(height)
            },
            '*'
        );
    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            sendHondaHeight
        );

    } else {

        sendHondaHeight();

    }


    window.addEventListener(
        'load',
        sendHondaHeight
    );


    if (
        typeof ResizeObserver !== 'undefined'
    ) {

        var observer =
            new ResizeObserver(
                sendHondaHeight
            );

        observer.observe(
            document.documentElement
        );

        if (document.body) {
            observer.observe(
                document.body
            );
        }
    }


    setTimeout(
        sendHondaHeight,
        100
    );

    setTimeout(
        sendHondaHeight,
        500
    );

})();
</script>
JS;


    /*
     * ------------------------------------------------------------
     * 9. NORMÁL OLDAL MÉRETEZÉSE
     * ------------------------------------------------------------
     *
     * Kapcsolási rajznál NEM fut.
     *
     * A normál oldal teljes Honda tartalmát egyben próbáljuk
     * a rendelkezésre álló iframe-szélességhez igazítani.
     */

    $fitScript = '';

    if (
        !$isWiringDiagram
    ) {

        $fitScript = <<<'JS'
<script>
(function () {

    function fitHondaContent() {

        var body =
            document.body;

        var html =
            document.documentElement;

        if (!body || !html) {
            return;
        }


        /*
         * A természetes tartalomszélesség.
         */

        var contentWidth =
            Math.max(
                body.scrollWidth,
                html.scrollWidth
            );


        var viewportWidth =
            html.clientWidth;


        if (
            contentWidth <= 0 ||
            viewportWidth <= 0
        ) {
            return;
        }


        /*
         * Ha eleve elfér, nincs szükség skálázásra.
         */

        if (
            contentWidth <= viewportWidth
        ) {

            body.style.transform = '';
            body.style.transformOrigin = '';
            body.style.width = '';

            sendHondaHeight();

            return;
        }


        /*
         * Az egész Honda dokumentum együtt skálázódik.
         */

        var scale =
            viewportWidth /
            contentWidth;


        body.style.transformOrigin =
            'top left';

        body.style.transform =
            'scale('
            + scale
            + ')';

        body.style.width =
            contentWidth
            + 'px';


        /*
         * A skálázás utáni tényleges magasság.
         */

        var naturalHeight =
            body.scrollHeight;

        var scaledHeight =
            naturalHeight *
            scale;


        window.parent.postMessage(
            {
                type: 'honda-iframe-height',
                height: Math.ceil(
                    scaledHeight
                )
            },
            '*'
        );
    }


    function sendHondaHeight() {

        var body =
            document.body;

        if (!body) {
            return;
        }

        var rect =
            body.getBoundingClientRect();

        window.parent.postMessage(
            {
                type: 'honda-iframe-height',
                height: Math.ceil(
                    rect.height
                )
            },
            '*'
        );
    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            fitHondaContent
        );

    } else {

        fitHondaContent();

    }


    window.addEventListener(
        'load',
        fitHondaContent
    );

    window.addEventListener(
        'resize',
        fitHondaContent
    );


    setTimeout(
        fitHondaContent,
        100
    );

    setTimeout(
        fitHondaContent,
        500
    );

})();
</script>
JS;
    }


    /*
     * ------------------------------------------------------------
     * 10. SAJÁT SCRIPTEK A BODY VÉGÉRE
     * ------------------------------------------------------------
     */

    $extraScripts =
        $cleanupScript
        . "\n"
        . $heightScript
        . "\n"
        . $fitScript;


    if (
        preg_match(
            '#</body>#i',
            $documentHtml
        )
    ) {

        $documentHtml =
            preg_replace(
                '#</body>#i',
                $extraScripts
                . "\n"
                . '</body>',
                $documentHtml,
                1
            );

    } else {

        $documentHtml .=
            "\n"
            . $extraScripts;
    }


    return $documentHtml;
}

function prepareHtmlFragment(
    string $html,
    string $type,
    int $year,
    string $jsDir
): string {



if (
    preg_match(
        '#<body\b[^>]*>(.*?)</body>#is',
        $html,
        $bodyMatch
    )
) {

    $fragment =
        $bodyMatch[1];

} else {

    $fragment =
        $html;
}




$fragment = preg_replace(
    '#(<div\b[^>]*\bid\s*=\s*["\']divBody["\'][^>]*\bstyle\s*=\s*["\'][^"\']*)display\s*:\s*none\s*;?\s*([^"\']*)["\']#i',
    '$1$2"',
    $fragment
);

$fragment = preg_replace(
    '#\s+onload\s*=\s*["\'][^"\']*["\']#i',
    '',
    $fragment
);


/*
 * Honda gyári külső JS fájlok feldolgozása
 *
 * A Zoom oldalak a rajzot nem közvetlenül
 * a HTML-ben tartalmazzák, hanem egy
 * külső JS fájl write(...) hívásaival
 * generálják.
 */
$fragment = preg_replace_callback(
    '#<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+\.js(?:\?[^"\']*)?)["\'][^>]*>\s*</script>#is',
    function ($match) use (
        $jsDir,
        $type,
        $year,
    	$html
    ) {

        $scriptSrc =
            trim($match[1]);

        $scriptPath =
            parse_url(
                $scriptSrc,
                PHP_URL_PATH
            );

        if (
            $scriptPath === false ||
            $scriptPath === null
        ) {
            return '';
        }

        $scriptFilename =
            basename(
                $scriptPath
            );

        if (
            $scriptFilename === ''
        ) {
            return '';
        }

        $jsFile =
            rtrim(
                $jsDir,
                '/\\'
            )
            . '/'
            . $scriptFilename;

        if (
            !is_file($jsFile)
        ) {
            return '';
        }

        return processJsFile(
    $jsFile,
    $type,
    $year,
    isHondaWiringDiagram($html)
);
    },
    $fragment
);


/*
 * A feldolgozatlan script tagek eltávolítása.
 */
$fragment = preg_replace(
    '#<script\b[^>]*>.*?</script>#is',
    '',
    $fragment
);



    $fragment = preg_replace(
        '#<!DOCTYPE\b[^>]*>#i',
        '',
        $fragment
    );



    $fragment = preg_replace(
        '#<html\b[^>]*>|</html>#i',
        '',
        $fragment
    );


    $fragment = preg_replace(
        '#<head\b[^>]*>.*?</head>#is',
        '',
        $fragment
    );


    $fragment = preg_replace(
        '#<body\b[^>]*>|</body>#i',
        '',
        $fragment
    );



    $fragment = preg_replace(
        '#<title\b[^>]*>.*?</title>#is',
        '',
        $fragment
    );



    $fragment = preg_replace(
        '#<meta\b[^>]*>#i',
        '',
        $fragment
    );



    $fragment = preg_replace(
        '#<link\b[^>]*>#i',
        '',
        $fragment
    );



    $fragment =
        convertImagePaths(
            $fragment
        );



    $fragment =
        convertManualLinks(
            $fragment,
            $type,
            $year
        );
        $fragment =
    convertManualWiringDiagram(
        $fragment,
        $type,
        (int)$year,
        $jsDir
    );



$fragment =
    convertManualLinks(
        $fragment,
        $type,
        $year
    );



$fragment =
    removeHondaPrintBlocks(
        $fragment
    );


/*
 * ------------------------------------------------------------
 * Honda iframe feldolgozás
 * ------------------------------------------------------------
 *
 * A kapcsolási rajz felismerése a .top_title tartalma
 * alapján történik.
 */

$isWiringDiagram =
    isWiringDiagram(
        $name
    );


$fragment =
    buildHondaIframeDocument(
        $html,
        $type,
        (int)$year,
        $jsDir,
        $isWiringDiagram
    );

$fragment = preg_replace(
    '#<div\b[^>]*style\s*=\s*["\'][^"\']*position\s*:\s*absolute\s*;\s*right\s*=\s*10\s*;?[^"\']*["\'][^>]*>.*?</div>#is',
    '',
    $fragment
);


if (
    strpos(
        $fragment,
        'class="honda-zoom"'
    ) !== false
) {

    $fragment =
        getHondaZoomCss()
        . "\n"
        . getHondaZoomJs()
        . "\n"
        . $fragment;
}



return trim($fragment);

}


function extractTitle(
    string $html
): string {

    if (
        preg_match(
            '#<title\b[^>]*>(.*?)</title>#is',
            $html,
            $match
        )
    ) {

        return trim(
            html_entity_decode(
                strip_tags($match[1]),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );
    }

    return '';
}



function extractName(
    string $html
): string {

    if (
        preg_match(
            '#<div\b[^>]*class\s*=\s*["\'][^"\']*\btop_title\b[^"\']*["\'][^>]*>(.*?)</div>#is',
            $html,
            $match
        )
    ) {

        return trim(
            html_entity_decode(
                strip_tags($match[1]),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );
    }

    return '';
}

function isWiringDiagram(
    string $title
): bool {

    return
        mb_stripos(
            $title,
            'kapcsolási rajz',
            0,
            'UTF-8'
        ) !== false;
}



function createWiringDiagramIframe(
    string $id
): string {

    $contentFile =
        $id
        . '-content.html';

    return
        '<div'
        . ' class="manual-wiring-diagram"'
        . ' style="'
        . 'width:100%;'
        . 'margin:0;'
        . 'padding:0;'
        . 'overflow:hidden;'
        . '">'
        . "\n"

        . '<iframe'
        . ' src="/manual/html/'
        . rawurlencode($contentFile)
        . '"'
        . ' title="Kapcsolási rajz"'
        . ' style="'
        . 'display:block;'
        . 'width:100%;'
        . 'height:calc(100vh - 100px);'
        . 'min-height:800px;'
        . 'border:0;'
        . 'margin:0;'
        . 'padding:0;'
        . '"'
        . ' loading="eager"'
        . ' scrolling="auto"'
        . '></iframe>'

        . "\n"
        . '</div>';
}


echo '<h2>Manual HTML feldolgozás</h2>';

echo '<p>Forrás: '
    . htmlspecialchars($sourceDir)
    . '</p>';

echo '<p>JS: '
    . htmlspecialchars($jsDir)
    . '</p>';

echo '<p>HTML cél: '
    . htmlspecialchars($htmlTargetDir)
    . '</p>';

echo '<p>JSON cél: '
    . htmlspecialchars($jsonTargetDir)
    . '</p>';

echo '<hr>';


$processed = 0;
$skipped = 0;
$errors = 0;


foreach ($files as $file) {

    $filename =
        basename($file);



    $id =
        pathinfo(
            $filename,
            PATHINFO_FILENAME
        );


    if ($id === '') {

        $skipped++;

        echo '<p>⚠ Kihagyva: '
            . htmlspecialchars($filename)
            . '</p>';

        continue;
    }



    $html =
        file_get_contents($file);


    if ($html === false) {

        $errors++;

        echo '<p>❌ Nem olvasható: '
            . htmlspecialchars($filename)
            . '</p>';

        continue;
    }



    $title =
        extractTitle($html);



    $name =
        extractName($html);



    $fragment =
        prepareHtmlFragment(
            $html,
            $type,
            (int)$year,
            $jsDir
        );
if (
    isWiringDiagram($title)
) {

    $contentFile =
        $htmlTargetDir
        . '/'
        . $id
        . '-content.html';



    if (
        file_put_contents(
            $contentFile,
            $fragment
        ) === false
    ) {

        $errors++;

        echo '<p>❌ Kapcsolási rajz mentési hiba: '
            . htmlspecialchars($contentFile)
            . '</p>';

        continue;
    }



    $fragment =
        '<div'
        . ' class="manual-wiring-diagram"'
        . ' style="'
        . 'width:100%;'
        . 'margin:0;'
        . 'padding:0;'
        . '">'
        . '<iframe'
        . ' src="/manual/html/'
        . rawurlencode($id . '-content.html')
        . '"'
        . ' style="'
        . 'display:block;'
        . 'width:100%;'
        . 'height:calc(100vh - 100px);'
        . 'min-height:800px;'
        . 'border:0;'
        . 'margin:0;'
        . 'padding:0;'
        . '"'
        . ' scrolling="auto"'
        . '></iframe>'
        . '</div>';
}

    if ($fragment === '') {

        $errors++;

        echo '<p>❌ Üres HTML: '
            . htmlspecialchars($filename)
            . '</p>';

        continue;
    }



$isWiringDiagram =
    isWiringDiagram(
        $title
    );



$htmlFile =
    $htmlTargetDir
    . '/'
    . $id
    . '.html';


if ($isWiringDiagram) {



    $contentFile =
        $htmlTargetDir
        . '/'
        . $id
        . '-content.html';


    if (
        file_put_contents(
            $contentFile,
            $fragment
        ) === false
    ) {

        $errors++;

        echo '<p>❌ Kapcsolási rajz tartalom mentési hiba: '
            . htmlspecialchars($contentFile)
            . '</p>';

        continue;
    }



    $displayHtml =
        createWiringDiagramIframe(
            $id
        );


    if (
        file_put_contents(
            $htmlFile,
            $displayHtml
        ) === false
    ) {

        $errors++;

        echo '<p>❌ Kapcsolási rajz iframe mentési hiba: '
            . htmlspecialchars($htmlFile)
            . '</p>';

        continue;
    }

} else {


    if (
        file_put_contents(
            $htmlFile,
            $fragment
        ) === false
    ) {

        $errors++;

        echo '<p>❌ HTML mentési hiba: '
            . htmlspecialchars($htmlFile)
            . '</p>';

        continue;
    }


    $displayHtml =
        $fragment;
}


$data = [

    'id' =>
        $id,

    'type' =>
        strtoupper($type),

    'year' =>
        (int)$year,

    'source' =>
        $filename,

    'title' =>
        $title,

    'name' =>
        $name,

    'wiring_diagram' =>
        $isWiringDiagram,

    'html' =>
        $displayHtml

];


    $json =
        json_encode(
            $data,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


    if ($json === false) {

        $errors++;

        echo '<p>❌ JSON hiba: '
            . htmlspecialchars($filename)
            . '</p>';

        continue;
    }



    $jsonFile =
        $jsonTargetDir
        . '/'
        . $id
        . '.json';


    if (
        file_put_contents(
            $jsonFile,
            $json
        ) === false
    ) {

        $errors++;

        echo '<p>❌ JSON mentési hiba: '
            . htmlspecialchars($jsonFile)
            . '</p>';

        continue;
    }


    $processed++;


    echo '<p>✓ '
        . htmlspecialchars($filename)
        . ' → '
        . htmlspecialchars($id . '.html')
        . ' + '
        . htmlspecialchars($id . '.json')
        . '</p>';
}

echo '<hr>';

echo '<h3>Feldolgozás kész</h3>';

echo '<p>Sikeres: '
    . $processed
    . '</p>';

echo '<p>Kihagyva: '
    . $skipped
    . '</p>';

echo '<p>Hibás: '
    . $errors
    . '</p>';
