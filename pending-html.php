<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

$type = strtolower(trim((string)($_GET['type'] ?? 'cn1')));
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

if ($type === '' || $year === false || $year === null || $year <= 0) {
    exit('Érvénytelen típus vagy évjárat.');
}

$sourceDir = __DIR__ . '/html-src';
$jsDir = __DIR__ . '/js';
$htmlTargetDir = __DIR__ . '/html';
$jsonTargetDir = __DIR__ . '/html/data';

if (!is_dir($sourceDir)) {
    exit('A forrásmappa nem található: ' . htmlspecialchars($sourceDir));
}

if (!is_dir($jsDir)) {
    exit('A JS mappa nem található: ' . htmlspecialchars($jsDir));
}

if (!is_dir($htmlTargetDir) && !mkdir($htmlTargetDir, 0775, true)) {
    exit('A HTML célmappa létrehozása sikertelen.');
}

if (!is_dir($jsonTargetDir) && !mkdir($jsonTargetDir, 0775, true)) {
    exit('A JSON célmappa létrehozása sikertelen.');
}

$files = array_merge(
    glob($sourceDir . '/*.html') ?: [],
    glob($sourceDir . '/*.htm') ?: []
);

if (!$files) {
    exit('Nem található HTML fájl a html-src mappában.');
}


function convertImagePaths(string $html): string
{
    return preg_replace_callback(
        '#(<(?:img\b[^>]*|input\b[^>]*\btype\s*=\s*["\']image["\'][^>]*)\bsrc\s*=\s*["\'])([^"\']+)(["\'][^>]*>)#i',
        function ($match) {
            $src = trim($match[2]);

            if ($src === '' || preg_match('#^(?:https?:)?//#i', $src)) {
                return $match[0];
            }

            $path = parse_url($src, PHP_URL_PATH);
            if ($path === false || $path === null) {
                return $match[0];
            }

            $filename = basename($path);
            if ($filename === '') {
                return $match[0];
            }

            $filename = preg_replace('/\.png$/i', '.PNG', $filename);

            return $match[1]
                . '/manual/images/'
                . rawurlencode($filename)
                . $match[3];
        },
        $html
    );
}

function convertJsImagePaths(string $js): string
{
    $js = str_replace(
        ['../img/', './img/', 'img/'],
        '/manual/images/',
        $js
    );

    return $js;
}

function convertManualLinks(
    string $html,
    string $type,
    int $year,
    ?int $carId = null
): string {

    /*
     * CtsProc linkek átalakítása
     */
    $html = preg_replace_callback(
        '#javascript\s*:\s*CtsProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\s*\)\s*;?#i',
        function ($match) use ($type, $year, $carId) {

            $targetId = trim($match[2]);
            $anc      = trim($match[3]);

            $url =
                '/manual/view.php'
                . '?type=' . rawurlencode($type)
                . '&year=' . rawurlencode((string)$year)
                . '&id=' . rawurlencode($targetId);

            /*
             * Ha van Anc paraméter,
             * továbbadjuk anc-ként a view.php számára.
             */
            if ($anc !== '') {
                $url .=
                    '&anc='
                    . rawurlencode($anc);
            }

            /*
             * Aktuális autó.
             */
            if ($carId !== null) {
                $url .=
                    '&car='
                    . rawurlencode((string)$carId);
            }

            return $url;
        },
        $html
    );


    /*
     * PrtProc linkek átalakítása
     */
    $html = preg_replace_callback(
        '#javascript\s*:\s*PrtProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]*)[\'"])?\s*\)\s*;?#i',
        function ($match) use ($type, $year, $carId) {

            $targetId = trim($match[2]);
            $anc      = isset($match[3])
                ? trim($match[3])
                : '';

            $url =
                '/manual/view.php'
                . '?type=' . rawurlencode($type)
                . '&year=' . rawurlencode((string)$year)
                . '&id=' . rawurlencode($targetId);

            /*
             * Ha van Anc paraméter,
             * továbbadjuk anc-ként.
             */
            if ($anc !== '') {
                $url .=
                    '&anc='
                    . rawurlencode($anc);
            }

            /*
             * Aktuális autó.
             */
            if ($carId !== null) {
                $url .=
                    '&car='
                    . rawurlencode((string)$carId);
            }

            return $url;
        },
        $html
    );

    return $html;
}


function convertJmpLinks(string $html): string
{
    return preg_replace_callback(
        '~javascript\s*:\s*parent\.Jmp\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)~i',
        function ($match) {
            $target = $match[1];

            return '#'
                . $target
                . '" onclick="window.location.hash=\''
                . htmlspecialchars($target, ENT_QUOTES, 'UTF-8')
                . '\'; return false;';
        },
        $html
    );
}
function extractTitle(string $html): string
{
    if (preg_match('#<title\b[^>]*>(.*?)</title>#is', $html, $match)) {
        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}


function extractName(string $html): string
{
    if (preg_match(
        '#<div\b[^>]*class\s*=\s*["\'][^"\']*\btop_title\b[^"\']*["\'][^>]*>(.*?)</div>#is',
        $html,
        $match
    )) {
        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}


function isWiringDiagram(string $title): bool
{
    return mb_stripos($title, 'kapcsolási rajz', 0, 'UTF-8') !== false;
}


function removeHondaButtons(string $html): string
{
    /*
     * A Honda oldalak kétféle kezelőgomb-blokkot használnak.
     * Mindkettőt szöveges regex-szel távolítjuk el.
     */

    $html = preg_replace(
        '#<div\b[^>]*>\s*(?:(?:<input\b[^>]*(?:PRINT_PREVIEW|GL_PREV(?:_DISABLE)?|GL_NEXT(?:_DISABLE)?|GL_CANCEL)[^>]*>\s*)+)</div>\s*<br\s*/?>#is',
        '',
        $html
    );

    $html = preg_replace(
        '#<div\b[^>]*>\s*(?:(?:<input\b[^>]*onClick\s*=\s*["\'](?:PrintFunc|BackFunc|NextFunc|CloseFunc)\s*\([^"\']*\)["\'][^>]*>\s*)+)</div>\s*<br\s*/?>#is',
        '',
        $html
    );

    $html = preg_replace(
        '#<input\b[^>]*(?:PRINT_PREVIEW|GL_PREV(?:_DISABLE)?|GL_NEXT(?:_DISABLE)?|GL_CANCEL)[^>]*>#is',
        '',
        $html
    );

    $html = preg_replace(
        '#<input\b[^>]*onClick\s*=\s*["\'](?:PrintFunc|BackFunc|NextFunc|CloseFunc)\s*\([^"\']*\)["\'][^>]*>#is',
        '',
        $html
    );

    /* A kapcsolási rajz oldalon lévő külön IMAGE_DISPLAY_RD gomb. */
    $html = preg_replace(
        '#<img\b[^>]*\bsrc\s*=\s*["\'][^"\']*IMAGE_DISPLAY_RD\.PNG[^"\']*["\'][^>]*>#is',
        '',
        $html
    );

    return $html;
}


function removeExternalScriptTags(string $html): string
{
    return preg_replace(
        '#<script\b[^>]*\bsrc\s*=\s*["\'][^"\']+\.js(?:\?[^"\']*)?["\'][^>]*>\s*</script>#is',
        '',
        $html
    );
}


function loadAndInlineHondaScripts(
    string $html,
    string $jsDir,
    string $type,
    int $year
): string {
    return preg_replace_callback(
        '#<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+\.js(?:\?[^"\']*)?)["\'][^>]*>\s*</script>#is',
        function ($match) use ($jsDir, $type, $year) {
            $scriptPath = parse_url(trim($match[1]), PHP_URL_PATH);

            if ($scriptPath === false || $scriptPath === null) {
                return '';
            }

            $filename = basename($scriptPath);
            if ($filename === '') {
                return '';
            }

            $jsFile = rtrim($jsDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
            if (!is_file($jsFile)) {
                return '';
            }

            $js = file_get_contents($jsFile);
            if ($js === false) {
                return '';
            }

            $js = convertJsImagePaths($js);
            $js = convertManualLinks($js, $type, $year);

            return "<script>\n" . $js . "\n</script>";
        },
        $html
    );
}
function fixHondaVmlContainerSizes(string $html): string
{
    /*
     * A VML grafikák tényleges méretét a <v:group> style
     * attribútumából olvassuk ki.
     *
     * A konténer magasságához 20 px ráhagyást használunk.
     *
     * Jelenleg kétféle Honda struktúrát kezelünk:
     *
     * 1. ViewerTD
     *    <td class="ViewerTD"> ... VML ... </td>
     *
     * 2. Egyszerű DIV
     *    <div>
     *        <script> ... VML ... </script>
     *    </div>
     *
     * A VML tartalmát egyik esetben sem módosítjuk.
     * Csak a megfelelő HTML konténer méretét állítjuk be.
     */

    $VML_CONTAINER_HEIGHT_EXTRA = 20;

    /*
     * A script blokkokat maszkoljuk a TD-k kereséséhez.
     *
     * A maszk pontosan ugyanakkora hosszúságú, mint az eredeti
     * script, ezért az offsetek továbbra is az eredeti HTML-re
     * mutatnak.
     */
    $scanHtml = preg_replace_callback(
        '#<script\b[^>]*>.*?</script\s*>#is',
        function ($match) {
            return str_repeat(' ', strlen($match[0]));
        },
        $html
    );

    if ($scanHtml === null) {
        return $html;
    }

    /*
     * ============================================================
     * 1. VML -> ViewerTD
     * ============================================================
     *
     * Ez a korábban stabilan működő logika.
     */

    preg_match_all(
        '#<td\b[^>]*\bclass\s*=\s*(["\'])[^"\']*\bViewerTD\b[^"\']*\1[^>]*>#is',
        $scanHtml,
        $tdMatches,
        PREG_OFFSET_CAPTURE
    );

    $replacements = [];

    if (!empty($tdMatches[0])) {

        foreach ($tdMatches[0] as $tdMatch) {

            $tdOpenTag = $tdMatch[0];
            $tdStart   = $tdMatch[1];
            $tdOpenEnd = $tdStart + strlen($tdOpenTag);

            /*
             * Megkeressük a TD lezárását a maszkolt HTML-ben.
             */
            $tdClose = stripos(
                $scanHtml,
                '</td',
                $tdOpenEnd
            );

            if ($tdClose === false) {
                continue;
            }

            /*
             * A TD teljes belső tartalma az eredeti HTML-ből.
             */
            $content = substr(
                $html,
                $tdOpenEnd,
                $tdClose - $tdOpenEnd
            );

            /*
             * Csak VML-t tartalmazó TD érdekel minket.
             */
            $vmlStart = stripos(
                $content,
                '<v:group'
            );

            if ($vmlStart === false) {
                continue;
            }

            /*
             * A Honda VML-ben a style attribútum escaped quote-okkal
             * szerepel:
             *
             * style=\"position:relative; width:475px; height:144px;\"
             */
            $styleStart = stripos(
                $content,
                'style=\"',
                $vmlStart
            );

            if ($styleStart === false) {
                continue;
            }

            $styleStart += strlen('style=\"');

            $styleEnd = strpos(
                $content,
                '\"',
                $styleStart
            );

            if ($styleEnd === false) {
                continue;
            }

            $groupStyle = substr(
                $content,
                $styleStart,
                $styleEnd - $styleStart
            );

            /*
             * Width.
             */
            if (!preg_match(
                '#\bwidth\s*:\s*(\d+(?:\.\d+)?)px\b#i',
                $groupStyle,
                $widthMatch
            )) {
                continue;
            }

            /*
             * Height.
             */
            if (!preg_match(
                '#\bheight\s*:\s*(\d+(?:\.\d+)?)px\b#i',
                $groupStyle,
                $heightMatch
            )) {
                continue;
            }

            $width  = (float)$widthMatch[1];
            $height = (float)$heightMatch[1]
                    + $VML_CONTAINER_HEIGHT_EXTRA;

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $widthValue = rtrim(
                rtrim((string)$width, '0'),
                '.'
            );

            $heightValue = rtrim(
                rtrim((string)$height, '0'),
                '.'
            );

            /*
             * Van már style attribútum?
             */
            if (preg_match(
                '#\bstyle\s*=\s*(["\'])(.*?)\1#is',
                $tdOpenTag,
                $styleMatch
            )) {

                $quote = $styleMatch[1];
                $style = $styleMatch[2];

                /*
                 * Korábbi width / height eltávolítása.
                 */
                $style = preg_replace(
                    '#(?:^|;)\s*width\s*:\s*[^;]+;?#i',
                    '',
                    $style
                );

                $style = preg_replace(
                    '#(?:^|;)\s*height\s*:\s*[^;]+;?#i',
                    '',
                    $style
                );

                $style = trim($style);

                if ($style !== '' && substr($style, -1) !== ';') {
                    $style .= ';';
                }

                $style .=
                    ' width:' . $widthValue . 'px;';

                $style .=
                    ' height:' . $heightValue . 'px;';

                $newTdOpenTag = preg_replace(
                    '#\bstyle\s*=\s*(["\']).*?\1#is',
                    'style=' . $quote . $style . $quote,
                    $tdOpenTag,
                    1
                );

            } else {

                /*
                 * Nincs style attribútum.
                 */
                $newTdOpenTag =
                    substr($tdOpenTag, 0, -1)
                    . ' style="width:'
                    . $widthValue
                    . 'px;height:'
                    . $heightValue
                    . 'px;">';
            }

            /*
             * Biztonsági ellenőrzés.
             */
            if (
                !is_string($newTdOpenTag) ||
                $newTdOpenTag === $tdOpenTag
            ) {
                continue;
            }

            /*
             * Csak a TD nyitó tagjét cseréljük.
             */
            $replacements[] = [
                'start'       => $tdStart,
                'length'      => strlen($tdOpenTag),
                'replacement' => $newTdOpenTag
            ];
        }
    }

    /*
     * ============================================================
     * 2. VML -> egyszerű DIV
     * ============================================================
     *
     * Az új Honda struktúra:
     *
     * <div>
     * <script>
     *     ... VML ...
     * </script>
     * </div>
     *
     * Ennél a DIV-nek nincs eredeti attribútuma.
     *
     * A VML width értékét változatlanul használjuk.
     * A VML height értékéhez +20 px kerül.
     *
     * A script és annak teljes tartalma változatlan marad.
     */

    preg_match_all(
        '#<div>\s*<script\b[^>]*>.*?<v:group\b.*?</script\s*>\s*</div>#is',
        $html,
        $divMatches,
        PREG_OFFSET_CAPTURE
    );

    if (!empty($divMatches[0])) {

        foreach ($divMatches[0] as $divMatch) {

            $divBlock  = $divMatch[0];
            $divStart  = $divMatch[1];

            /*
             * Megkeressük a VML <v:group> kezdetét.
             */
            $vmlStart = stripos(
                $divBlock,
                '<v:group'
            );

            if ($vmlStart === false) {
                continue;
            }

            /*
             * A VML group style attribútuma.
             */
            $styleStart = stripos(
                $divBlock,
                'style=\"',
                $vmlStart
            );

            if ($styleStart === false) {
                continue;
            }

            $styleStart += strlen('style=\"');

            $styleEnd = strpos(
                $divBlock,
                '\"',
                $styleStart
            );

            if ($styleEnd === false) {
                continue;
            }

            $groupStyle = substr(
                $divBlock,
                $styleStart,
                $styleEnd - $styleStart
            );

            /*
             * Width.
             */
            if (!preg_match(
                '#\bwidth\s*:\s*(\d+(?:\.\d+)?)px\b#i',
                $groupStyle,
                $widthMatch
            )) {
                continue;
            }

            /*
             * Height.
             */
            if (!preg_match(
                '#\bheight\s*:\s*(\d+(?:\.\d+)?)px\b#i',
                $groupStyle,
                $heightMatch
            )) {
                continue;
            }

            $width  = (float)$widthMatch[1];
            $height = (float)$heightMatch[1]
                    + $VML_CONTAINER_HEIGHT_EXTRA;

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $widthValue = rtrim(
                rtrim((string)$width, '0'),
                '.'
            );

            $heightValue = rtrim(
                rtrim((string)$height, '0'),
                '.'
            );

            /*
             * A cél DIV a blokk elején található:
             *
             * <div>
             *
             * Ezt cseréljük:
             *
             * <div style="width:475px;height:373px;">
             */
            $newDivOpenTag =
                '<div style="width:'
                . $widthValue
                . 'px;height:'
                . $heightValue
                . 'px;">';

            /*
             * Az eredeti <div> hossza mindig 5 karakter:
             *
             * <div>
             */
            $replacements[] = [
                'start'       => $divStart,
                'length'      => 5,
                'replacement' => $newDivOpenTag
            ];
        }
    }

    /*
     * ============================================================
     * 3. Az összes módosítás végrehajtása
     * ============================================================
     *
     * Hátulról előre dolgozunk, hogy az egyik csere ne módosítsa
     * a következő csere pozícióját.
     */
    usort(
        $replacements,
        function ($a, $b) {
            return $b['start'] <=> $a['start'];
        }
    );

    foreach ($replacements as $replacement) {

        $html = substr_replace(
            $html,
            $replacement['replacement'],
            $replacement['start'],
            $replacement['length']
        );
    }

    return $html;
}

function addIframeHead(string $html): string
{
    $base = '<base target="_top">';
    

    $css = <<<'CSS'
<style>
html, body {
    margin: 0 !important;
    padding: 0 !important;
    background: #cccccc;
}
A:link {  COLOR: #000;}
A:active {  COLOR: #000;}
A:visited { COLOR: #000;}
A:hover { COLOR: #bb271a; TEXT-DECORATION: underline;}

img {
    max-width: none;
}
</style>
CSS;

    if (preg_match('#<head\b[^>]*>(.*?)</head>#is', $html, $match)) {
        $newHead = $base . "\n" . $viewerCss . "\n" . $css . "\n" . $match[1];

        return preg_replace(
            '#<head\b[^>]*>.*?</head>#is',
            '<head>' . $newHead . '</head>',
            $html,
            1
        );
    }

    return '<!DOCTYPE html>\n<html lang="hu">\n<head>\n'
        . $base . "\n" . $css
        . '</head>\n'
        . $html
        . '</html>';
}
function isZoomPage(string $filename, string $html = ''): bool
{
    /*
     * Régi Zoom oldalak:
     * a fájlnév ZOOM-mal kezdődik.
     */
    if (strncasecmp($filename, 'ZOOM', 4) === 0) {
        return true;
    }

    /*
     * Újabb Honda Zoom / Viewer oldalak:
     * nem feltétlenül ZOOM... nevű fájlok,
     * ezért a HTML szerkezete alapján is felismerjük őket.
     */
    $score = 0;

    if (stripos($html, 'ViewerStyle.css') !== false) {
        $score++;
    }

    if (stripos($html, 'gImageGroupList_') !== false) {
        $score++;
    }

    if (stripos($html, '_prResizeImage') !== false) {
        $score++;
    }

    if (stripos($html, 'jsResizeImage') !== false) {
        $score++;
    }

    if (stripos($html, 'xmlns:v="urn:schemas-microsoft-com:vml"') !== false) {
        $score++;
    }

    return $score >= 3;
}

function addNormalIframeResizeScript(string $html): string
{
    $js = <<<'JS'
<script>
(function () {
    function sendHeight() {
        var body = document.body;
        if (!body) return;

        var rect = body.getBoundingClientRect();
        var height = Math.ceil(rect.height);

        window.parent.postMessage({
            type: 'honda-iframe-height',
            height: height
        }, '*');
    }

    function fitContent() {
        var body = document.body;
        if (!body) return;

        var viewportWidth = document.documentElement.clientWidth;
        if (viewportWidth <= 0) return;

        var candidates = document.querySelectorAll('[style*="width:"][style*="px"]');
        var largestWidth = 0;

        candidates.forEach(function (element) {
            var style = window.getComputedStyle(element);
            var width = parseFloat(style.width);

            if (width > largestWidth) {
                largestWidth = width;
            }
        });

        if (largestWidth > viewportWidth && largestWidth > 0) {
            var scale = viewportWidth / largestWidth;

            body.style.transformOrigin = 'top left';
            body.style.transform = 'scale(' + scale + ')';
            body.style.width = largestWidth + 'px';
        } else {
            body.style.transform = '';
            body.style.transformOrigin = '';
            body.style.width = '';
        }

        sendHeight();
    }

    function ready() {
        fitContent();
        setTimeout(fitContent, 100);
        setTimeout(fitContent, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }

    window.addEventListener('load', fitContent);
    window.addEventListener('resize', fitContent);

    if (typeof ResizeObserver !== 'undefined') {
        var observer = new ResizeObserver(fitContent);
        if (document.documentElement) observer.observe(document.documentElement);
        if (document.body) observer.observe(document.body);
    }
})();
</script>
JS;

    if (stripos($html, '</body>') !== false) {
        return preg_replace('#</body>#i', $js . "\n</body>", $html, 1);
    }

    return $html . "\n" . $js;
}


function prepareIframeDocument(
    string $html,
    string $type,
    int $year,
    string $jsDir,
    bool $isWiringDiagram,
    bool $isZoom
): string {
    /*
     * Fontos: innen nincs DOM és nincs régi Zoom/VML átalakítás.
     * Az eredeti HTML marad, csak a szükséges szöveges módosításokat végezzük.
     */

$html = convertImagePaths($html);

$html = convertManualLinks($html, $type, $year);

$html = convertJmpLinks($html);

$html = removeHondaButtons($html);
$html = loadAndInlineHondaScripts($html, $jsDir, $type, $year);

$html = fixHondaVmlContainerSizes($html);





if ($isWiringDiagram) {
    $html = movePositionedLabelsUp($html, WIRING_LABEL_OFFSET);
}

if ($isZoom) {
    $html = movePositionedLabelsUp($html, ZOOM_LABEL_OFFSET);
}



$html = addIframeHead($html);

if (!$isWiringDiagram && !$isZoom) {
    $html = addNormalIframeResizeScript($html);
} elseif ($isWiringDiagram) {
    $css = '<style>html,body{overflow:auto !important;} img{max-width:none !important;}</style>';
    $html = preg_replace('#</head>#i', $css . "\n</head>", $html, 1);
} elseif ($isZoom) {
    $css = '<style>html,body{overflow:auto !important; overflow-x:auto !important; overflow-y:auto !important;} img{max-width:none !important;}</style>';
    $html = preg_replace('#</head>#i', $css . "\n</head>", $html, 1);
}
    return trim($html);
}

// Pozicionált feliratok függőleges eltolása pixelben.
// Pozitív érték = felfelé mozgatás.
const WIRING_LABEL_OFFSET = 5;
const ZOOM_LABEL_OFFSET = 10;

function movePositionedLabelsUp(
    string $html,
    int $offset
): string {
    return preg_replace_callback(
        '#(name=\\\\"PrtPId\\\\"[^>]*style=\\\\".*?\\btop\\s*:\\s*)(-?\d+(?:\.\d+)?)(px)#i',
        function ($match) use ($offset) {
            $top = (float)$match[2] - $offset;

            return $match[1] . $top . $match[3];
        },
        $html
    );
}
function createNormalIframe(string $id, bool $isZoom = false): string
{
    $contentFile = $id . '-content.html';

    $script = <<<'JS'
<script>
(function () {
    function resize(frame) {
        try {
            var doc = frame.contentDocument;
            if (!doc) return;

            var body = doc.body;
            var html = doc.documentElement;
            if (!body || !html) return;

            var height = Math.max(
                body.scrollHeight,
                body.offsetHeight,
                html.scrollHeight,
                html.offsetHeight
            );

            if (height > 0) {
                frame.style.height = Math.ceil(height) + 'px';
            }
        } catch (e) {}
    }

    var frame = document.getElementById('honda-iframe');
    if (!frame) return;

    frame.addEventListener('load', function () {
        resize(frame);
        setTimeout(function () { resize(frame); }, 100);
        setTimeout(function () { resize(frame); }, 500);
    });

    window.addEventListener('message', function (event) {
        if (event.source !== frame.contentWindow) return;
        if (!event.data || event.data.type !== 'honda-iframe-height') return;

        var height = parseInt(event.data.height, 10);
        if (height > 0) {
            frame.style.height = height + 'px';
        }
    });

    window.addEventListener('resize', function () {
        resize(frame);
    });
})();
</script>
JS;

    return '<div class="manual-content-iframe" style="width:100%;margin:0;padding:0;overflow:hidden;">'
. '<iframe id="honda-iframe" src="/manual/html/'
. rawurlencode($contentFile)
. '" style="display:block;width:100%;height:' . ($isZoom ? 'calc(100vh - 100px)' : '200px') . ';border:0;margin:0;padding:0;background:#cccccc;overflow:auto;" frameborder="0" scrolling="' . ($isZoom ? 'yes' : 'no') . '" loading="eager"></iframe>'
       . ($isZoom ? '' : $script)
        . '</div>';
}


function createWiringDiagramIframe(string $id): string
{
    $contentFile = $id . '-content.html';

    return '<div class="manual-wiring-diagram" style="width:100%;margin:0;padding:0;overflow:hidden;">'
        . '<iframe src="/manual/html/'
        . rawurlencode($contentFile)
        . '" title="Kapcsolási rajz" style="display:block;width:100%;height:calc(100vh - 100px);min-height:800px;border:0;margin:0;padding:0;background:#fff;" frameborder="0" scrolling="auto" loading="eager"></iframe>'
        . '</div>';
}


echo '<h2>Manual HTML feldolgozás</h2>';
echo '<p>Forrás: ' . htmlspecialchars($sourceDir) . '</p>';
echo '<p>JS: ' . htmlspecialchars($jsDir) . '</p>';
echo '<p>HTML cél: ' . htmlspecialchars($htmlTargetDir) . '</p>';
echo '<p>JSON cél: ' . htmlspecialchars($jsonTargetDir) . '</p>';
echo '<hr>';

$processed = 0;
$skipped = 0;
$errors = 0;

foreach ($files as $file) {
    $filename = basename($file);
    $id = pathinfo($filename, PATHINFO_FILENAME);

    if ($id === '') {
        $skipped++;
        echo '<p>⚠ Kihagyva: ' . htmlspecialchars($filename) . '</p>';
        continue;
    }

    $html = file_get_contents($file);

    if ($html === false) {
        $errors++;
        echo '<p>❌ Nem olvasható: ' . htmlspecialchars($filename) . '</p>';
        continue;
    }

    $title = extractTitle($html);
    $name = extractName($html);
   $isWiringDiagram = isWiringDiagram($name);
$isZoom = isZoomPage($filename, $html);

$fragment = prepareIframeDocument(
    $html,
    $type,
    (int)$year,
    $jsDir,
    $isWiringDiagram,
    $isZoom
);
if (stripos($fragment, '<td class="ViewerTD" colspan="5"') !== false) {

    $pos = stripos(
        $fragment,
        '<td class="ViewerTD" colspan="5"'
    );

    $end = stripos($fragment, '>', $pos);

    file_put_contents(
        __DIR__ . '/vml-debug.txt',
        "AFTER prepareIframeDocument:\n"
        . substr($fragment, $pos, $end - $pos + 1)
        . "\n================\n",
        FILE_APPEND
    );
}
    if ($fragment === '') {
        $errors++;
        echo '<p>❌ Üres HTML: ' . htmlspecialchars($filename) . '</p>';
        continue;
    }

    $htmlFile = $htmlTargetDir . '/' . $id . '.html';
    $contentFile = $htmlTargetDir . '/' . $id . '-content.html';

    if (file_put_contents($contentFile, $fragment) === false) {
        $errors++;
        echo '<p>❌ Tartalom mentési hiba: ' . htmlspecialchars($contentFile) . '</p>';
        continue;
    }

$displayHtml = $isWiringDiagram
    ? createWiringDiagramIframe($id)
    : createNormalIframe($id, $isZoom);

    if (file_put_contents($htmlFile, $displayHtml) === false) {
        $errors++;
        echo '<p>❌ Iframe mentési hiba: ' . htmlspecialchars($htmlFile) . '</p>';
        continue;
    }

    $data = [
        'id' => $id,
        'type' => strtoupper($type),
        'year' => (int)$year,
        'source' => $filename,
        'title' => $title,
        'name' => $name,
        'wiring_diagram' => $isWiringDiagram,
        'html' => $displayHtml
    ];

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        $errors++;
        echo '<p>❌ JSON hiba: ' . htmlspecialchars($filename) . '</p>';
        continue;
    }

    $jsonFile = $jsonTargetDir . '/' . $id . '.json';

    if (file_put_contents($jsonFile, $json) === false) {
        $errors++;
        echo '<p>❌ JSON mentési hiba: ' . htmlspecialchars($jsonFile) . '</p>';
        continue;
    }

    $processed++;

    echo '<p>✓ '
        . htmlspecialchars($filename)
        . ' → '
        . htmlspecialchars($id . '.html')
        . ' + '
        . htmlspecialchars($id . '-content.html')
        . ' + '
        . htmlspecialchars($id . '.json')
        . '</p>';
}

echo '<hr>';
echo '<h3>Feldolgozás kész</h3>';
echo '<p>Sikeres: ' . $processed . '</p>';
echo '<p>Kihagyva: ' . $skipped . '</p>';
echo '<p>Hibás: ' . $errors . '</p>';
