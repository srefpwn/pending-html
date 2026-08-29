<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';


/*
 * Típus, évjárat és oldal meghatározása
 *
 * Példa:
 *
 * /manual/view.php?type=cn1&year=2006&id=000000000003797
 */


/*
 * Típus
 */

$type = strtolower(
    trim((string)($_GET['type'] ?? ''))
);


/*
 * Évjárat
 */

$year = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT
);


/*
 * Oldal ID
 */

$id = trim(
    (string)($_GET['id'] ?? '')
);
/*
 * ZOOM nézet / rész meghatározása
 *
 * anc=1 → Első
 * anc=2 → Hátsó
 */
$anc = trim(
    (string)($_GET['anc'] ?? '')
);

if ($anc !== '1' && $anc !== '2') {
    $anc = '';
}

/*
 * Alapvető ellenőrzés
 */

if ($type === '') {
    die('Érvénytelen típus.');
}

if (
    $year === false ||
    $year === null ||
    $year <= 0
) {
    die('Érvénytelen évjárat.');
}

if ($id === '') {
    die('Hiányzó oldalazonosító.');
}


/*
 * Típus ellenőrzése
 */

$allowedTypes = [
    'cn1',
    'cl9'
];

if (!in_array($type, $allowedTypes, true)) {
    die('Érvénytelen típus.');
}


/*
 * ID ellenőrzése
 */

if (
    !preg_match('/^[0-9]{15}$/', $id) &&
    !preg_match('/^ZOOM[0-9]+(?:_PR)?$/i', $id)
) {
    die('Érvénytelen oldalazonosító.');
}


/*
 * JSON fájl elérési útja
 */

$jsonFile =
    $_SERVER['DOCUMENT_ROOT']
    . '/data/manual/honda/accord/series_7/json/'
    . $id
    . '.json';


/*
 * JSON fájl ellenőrzése
 */

if (!is_file($jsonFile)) {
    die('A kért manual oldal nem található.');
}


/*
 * JSON betöltése
 */

$json = file_get_contents($jsonFile);

if ($json === false) {
    die('A manual oldal nem olvasható.');
}


/*
 * JSON feldolgozása
 */

$page = json_decode(
    $json,
    true
);


if (!is_array($page)) {
    die(
        'JSON hiba: '
        . htmlspecialchars(
            json_last_error_msg()
        )
    );
}

/*
 * Manual linkek átalakítása
 *
 * Kezeli:
 *
 * CtsProc(...)
 * PrtProc(...)
 *
 * Mindkettőt saját RichCars
 * /manual/view.php linkké alakítjuk.
 */

function convertManualLinks(
    string $html,
    string $type,
    int $year,
    ?int $carId = null
): string {

    /*
     * --------------------------------------------------
     * CtsProc linkek
     * --------------------------------------------------
     *
     * Eredeti:
     *
     * javascript:CtsProc('0','000000000000009','000')
     *
     * Eredmény:
     *
     * /manual/view.php?type=cn1&year=2006&id=000000000000009
     */

    $html = preg_replace_callback(
        '#javascript\s*:\s*CtsProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]*)[\'"]\s*\)#i',
        function ($match) use ($type, $year, $carId) {

            $targetId = trim($match[2]);

            $url =
                '/manual/view.php'
                . '?type=' . rawurlencode($type)
                . '&year=' . rawurlencode((string)$year)
                . '&id=' . rawurlencode($targetId);


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
     * --------------------------------------------------
     * PrtProc linkek
     * --------------------------------------------------
     *
     * Eredeti:
     *
     * javascript:PrtProc('0','ZOOM000000000015863_PR')
     *
     * Eredmény:
     *
     * /manual/view.php?type=cn1&year=2006&id=ZOOM000000000015863_PR
     */

    $html = preg_replace_callback(
        '#javascript\s*:\s*PrtProc\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]*)[\'"])?\s*\)\s*;?#i',
        function ($match) use ($type, $year, $carId) {

            $targetId = trim($match[2]);

            $url =
                '/manual/view.php'
                . '?type=' . rawurlencode($type)
                . '&year=' . rawurlencode((string)$year)
                . '&id=' . rawurlencode($targetId);


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

$pageTitle =
    trim((string)($page['title'] ?? ''));

$pageName =
    trim((string)($page['name'] ?? ''));

$pageHtml =
    (string)($page['html'] ?? '');
/*
 * Ellenőrzés
 */

if ($pageHtml === '') {
    die('A manual oldal HTML tartalma üres.');
}


/*
 * Autó meghatározása, ha van car paraméter
 */

$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

$userCar = null;
$userCarConfig = null;


if ($carId !== false && $carId !== null) {

    $userCars = getUserCars();

    foreach ($userCars as $car) {

        if (
            isset($car['id']) &&
            (int)$car['id'] === $carId
        ) {
            $userCar = $car;
            break;
        }
    }


    /*
     * Ha van autó, lekérjük a konfigurációját.
     */

    if ($userCar !== null) {

        $userCarConfig =
            getCarConfig($userCar);


        if ($userCarConfig === null) {

            $userCar = null;
            $userCarConfig = null;
        }
    }
}
/*
 * CtsProc linkek átalakítása
 *
 * A HTML-ben található eredeti CtsProc
 * hivatkozásokat saját view.php linkekké
 * alakítjuk.
 */

$pageHtml = convertManualLinks(
    $pageHtml,
    $type,
    (int)$year,
    ($carId !== false && $carId !== null)
        ? (int)$carId
        : null
);

/*
 * Aktuális autó azonosítójának hozzáadása
 *
 * A már létező saját /manual/view.php linkekhez
 * hozzáadjuk az aktuális autó ID-ját.
 */
if ($carId !== false && $carId !== null) {

    $pageHtml = preg_replace_callback(
        '#/manual/view\.php\?[^"\'<>\s]+#i',
        function ($match) use ($carId) {

            $url = $match[0];

            /*
             * Ha már van car paraméter,
             * nem adjuk hozzá újra.
             */
            if (
                preg_match(
                    '#(?:[?&])car=[^&]+#i',
                    $url
                )
            ) {
                return $url;
            }

            /*
             * Aktuális autó ID hozzáadása.
             */
            return $url
                . '&car='
                . rawurlencode((string)$carId);
        },
        $pageHtml
    );
}
/*
 * ZOOM nézet
 *
 * anc=1 → első
 * anc=2 → hátsó
 */
$anc = trim(
    (string)($_GET['anc'] ?? '')
);

/*
 * ZOOM nézet kiválasztása
 *
 * anc=1 → első ábra
 * anc=2 → hátsó ábra
 *
 * A Honda eredeti JavaScriptje a #1 / #2
 * hash alapján választja ki az imgId1 / imgId2
 * tartalmat az iframe-en belül.
 */
 
if ($anc !== '' && ctype_digit($anc) && (int)$anc > 0) {

    $pageHtml = preg_replace(
        '#(<iframe\b[^>]*\bsrc\s*=\s*["\'][^"\']+)(["\'])#i',
        '$1#' . $anc . '$2',
        $pageHtml,
        1
    );
}

/*
 * Vissza link
 */

$back = $_GET['back'] ?? '';

if ($back !== '') {

    $returnUrl = $back;

} else {

    $returnUrl = 'javascript:history.back()';
}

?>

<html>
<head>
	<title>RichCars - Szerviz kézikönyv</title>
		<meta charset="UTF-8">
		<link rel="icon" href="/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
		<link href="/css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<table class="table-100-center site-bg">
	<tr>
		<td>
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/menu.php'; ?>
		 <table class="table-100-center">
            <tr>
                <td class="submenu textv-top">

                    <table width="100%" style="width:100%;" align="left">
                        <tr>
                            <td class="textv-top" style="line-height: 40px;">
                            <a href="<?= htmlspecialchars($returnUrl) ?>" class="leftmenu-back">Vissza</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align="left">
                <table style="width:100%;">
                    <tr>
                        <td>
                        <table align="left" width="100%" class="table-border">
                            <tr>
                                <td class="textv-top">
                                <table align="left" class="table-border" width="100%">
                                	<tr>
                                		<td style="padding:0px;text-align:center;">
    									<span class="epc-title">
    									<?php if ($userCar !== null): ?>
        								<?php if (trim((string)($userCar['name'] ?? '')) !== ''): ?>
            							<?= htmlspecialchars($userCar['name']) ?> - 
        								<?php endif; ?>
        								<?= htmlspecialchars($userCar['vin']) ?> - 
        								<?= htmlspecialchars($pageName) ?> - Szerviz kézikönyv
    									<?php else: ?>
        								<?= htmlspecialchars($pageName) ?> - Szerviz kézikönyv
    									<?php endif; ?>
    									</span>
										</td>
                                    </tr>
                                    <tr>
										<td style="padding:20px;text-align:center;">
										<table style="width:100%;">
											<tr>
												<td style="text-align:center;padding-bottom:20px;">
												<img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($current['title']) ?>" width="100%">
												</td>
											</tr>
										</table>
										<table style="width:100%;text-align:left;border-spacing:0px;padding-bottom:20px;" cellspacing="0" cellpadding="0">
											<tr>
												<td>
												</td>
											</tr>
											<tr>
												<td style="background-color:#cccccc; padding:20px; text-align:left; color:#000000; color:#000000;">
<style>
div.Header	{
				font-family:		"Arial";
				font-weight:		bold;
				font-size:			2em;
				width:				100%;
				filter:				shadow(color=gray,direction=135);
			}
.Label		{
				font-size:			small;
			}
div.title	{
				background-color:	lightgrey;
				font-family:		Arial,sans-serif;
				font-size:			x-small;
				font-weight:		bold;
				padding:			3px;
			}
div.abbrev	{
				margin-top:			8px;
				font-family:		Arial,sans-serif;
			}
input.abbrev{
				font-family:		Arial,sans-serif;
				font-size:			x-small;
				font-weight:		bold;
			}
ul			{
				list-style-type:	none;
				margin-left:		1em;
				margin-top:			0px;
			}
img.illust	{
				border-style:		none;
				position:			relative;
				top:				4px;
			}
img.maru	{
				position:			relative;
				top:				2px;
				border:				0;
			}

.manual-page-content {
    color: #000000;
}

.manual-page-content * {
    color: #000000;
}
.manual-page-content img {
    max-width: 100%;
    height: auto;
}

TABLE.Viewer{
border-collapse:collapse;
border-color:black;
empty-cells:show;
}

TH.ViewerTH{
border-color:black;
border-width:1pt;
font-weight:normal;
font-size:10pt;
vertical-align:top;
}
TD.ViewerTD{
border-color:black;
border-width:1pt;
font-weight:normal;
font-size:10pt;
line-height:20px;
vertical-align:top;
}

/* ÉTÉuÉ^ÉCÉgÉã */
TABLE.SubTitle {
  border          : 0 outset;
  border-width    : 0px;
  padding         : 0px;
  border-collapse : collapse;
  width           : 100%;
  height          : 24;
  margin-Top      : 8px; 
  margin-Bottom   : 3px; 
  color           : #000000;
  background-color: #CCCCCC;
}
TD.SubTitle-L {
  width           : 13px;
  color           : #000000;
  background-color: #CCCCCC;
  background-image: url(../img/TITLEBER_C.PNG);
  background-position:left bottom;
  background-repeat:no-repeat;
}
TD.SubTitle {
  font-size:12pt;
  font-weight     : bold;
  text-align      : left;
  background-color: #CCCCCC;
  color           : #000000;
  vertical-align:middle;
}
TD.SubTitleComment {
  text-align      : left;
  text-valign     : bottom;
  width           : 70%;
  background-color: #CCCCCC;
  color           : #000000;
}
TD.SubTitle-R {
  text-align      : right;
  width           : 7px;
  height          : 19px;
}

/*---------------------------*/
/* ï\ÉeÅ[ÉuÉãïîï™             */
/*---------------------------*/
.TableLayout {
  margin-left    : 20px; 
  background-color: "";
  border-style: none;
 /* vertical-align: top;*/
}
.TableLayout td {
	  vertical-align: top;
}
/* ï\ÉeÅ[ÉuÉãÇÃÉ^ÉCÉgÉã */
.TableTite {
  color           : #FFFFFF;
  background-color: #FF00FF;
}
/* ï\ÉeÅ[ÉuÉãÇÃï\ëË */
.TableColumnTitle {
  color           : #FFFFFF;
  text-align      : left;
  background-color: #999999;
  white-space     : nowrap;
  text-align      : center;
}


/* ï\ÉeÅ[ÉuÉãÇÃÉfÅ[É^ïî */
.TableData {
  color           : #000000;
  background-color: #FFFFFF;
  text-align      : left;
  white-space     : nowrap;
  font-weight     : normal;
}


/********************************************************/
/*****                 É^ÉCÉgÉãíËã`                 *****/
/********************************************************/
.top_title{
font-size:12pt;
font-weight:bold;
font-family:'Arial';
}

.link_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.figure_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.list1_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.table_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.tool_list_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.test_procedure_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.book_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.exploded_view_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.procedure_topic_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.servinfosub_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.procedure_sub_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.system_desc_sub_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.topic_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.mentenance_item_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.dis_mentenance_item_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.com_mentenance_item_title{
font-style:italic;
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}

.zoom_title{
font-size:11pt;
font-weight:bold;
padding-top:3px;
padding-left:5px;
font-family:'Arial';
}
/********************************************************/
/*****               É^ÉOÉXÉ^ÉCÉãíËã`               *****/
/********************************************************/

.list1{
list-style-position:outside;
padding-top:3px;
padding-bottom:3px;
padding-left:10px;
}

.list2{
list-style-position:outside;
padding-top:0px;
padding-bottom:0px;
padding-left:10px;
}

.warning_head{
background-color:#F9A13A;
text-align:center;
vertical-align:middle;
color:white;
}

.warning_body{
}

.caution_head{
background-color:#FFE153;
text-align:center;
vertical-align:middle;
color:white;
}

.caution_body{
}
.expcolelem      {padding:2.5pt 2.5pt 2.5pt 2.5pt;}
.expcolelemtitle {font-size:11pt;
                  font-weight:bold;
                  padding-top:3pt;}
.expcolpart      {display:block;
                  padding:10pt 2.5pt 2.5pt 2.5pt;}
.s1cont          {padding-left:30px;
                  padding-top:5px;
                  padding-bottom:5px;}
.s1              {line-height: 16px;
                  text-indent: -30px;
                  text-align: right;
                  position:relative;
                  top:0px;
                  left:0px;
                  width:30px;}
.s2cont          {padding-left:30px;
                  padding-top:0px;
                  padding-bottom:0px;}
.s2              {line-height: 16px;
                  text-indent: -30px;
                  text-align: right;
                  position:relative;
                  top:0px;
                  left:0px;
                  width:30px;}
.question        {padding-left:47px;
                  padding-top:15px;
                  padding-bottom:1px;}
.resactcont      {padding-left:65px;
                  padding-top:1px;
                  padding-bottom:1px;}
.dtc             {font-size:11pt;}
.dtc-desc        {padding-top:10px;
                  padding-bottom:5px;
                  padding-left:7px;}

.tool            {padding-top:0px;
                  padding-bottom:0px;
                  padding-left:10px;}
.toolbull        {line-height: 16px;
                  text-indent: -10px;
                  text-align: right;
                  position:relative;
                  top:0px;
                  left:0px;
                  width:10px;}
.attention       {padding-top:2px;
                  padding-bottom:2px;}
.ti-procedure-title {font-size:11pt;
                  padding-top:3px;
                  padding-left:5px;
                  font-family:'Arial';}
.ti-procedurecont {padding-left:30px;}
.ti-procedure    {line-height: 16px;
                  text-indent: -30px;
                  text-align: right;
                  position:relative;
                  top:0px;
                  left:0px;
                  width:30px;}
.ti-check-title   {font-size:11pt;
                  padding-top:3px;
                  padding-left:5px;
                  font-family:'Arial';}
.ti-checkcont    {padding-left:30px;}
.ti-check        {line-height: 16px;
                  text-indent: -30px;
                  text-align: right;
                  position:relative;
                  top:0px;
                  left:0px;
                  width:30px;}
.figtitle        {font-size:10pt;
                  font-weight:bold;
                  padding-top:3px;
                  padding-left:8px;}
.ptxt01          {padding-left: 5px;}
.ptxt02          {padding-left: 10px;}
.ptxt03          {padding-left: 15px;}
.ptxt04          {padding-left: 20px;}
.ptxt05          {padding-left: 25px;}
.ptxt06          {padding-left: 30px;}
.ptxt07          {padding-left: 35px;}
.ptxt08          {padding-left: 40px;}
.ptxt09          {padding-left: 45px;}
.ptxt10          {padding-left: 50px;}
.commonitem      {padding-top:0px;
                 padding-bottom:0px;
                 padding-left:10px;}
.maintenanceitem {padding-top:0px;
                  padding-bottom:0px;
                  padding-left:10px;}
.subtitle  		 {padding-top:0px;
                  padding-bottom:0px;
                  text-align: center;
 				  font-weight:bold;}
.distancetitle	 {font-size:13pt;
				  text-align: right;
				  font-weight:bold;}
.sysdescindexList{padding-left: 10px;
                  text-weight:bold;}

.entry_border_0{
border-top-style:none;
border-bottom-style:none;
border-left-style:none;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_1{
border-top-style:none;
border-bottom-style:none;
border-left-style:none;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_2{
border-top-style:none;
border-bottom-style:none;
border-left-style:solid;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_3{
border-top-style:none;
border-bottom-style:none;
border-left-style:solid;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_4{
border-top-style:none;
border-bottom-style:solid;
border-left-style:none;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_5{
border-top-style:none;
border-bottom-style:solid;
border-left-style:none;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_6{
border-top-style:none;
border-bottom-style:solid;
border-left-style:solid;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_7{
border-top-style:none;
border-bottom-style:solid;
border-left-style:solid;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_8{
border-top-style:solid;
border-bottom-style:none;
border-left-style:none;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_9{
border-top-style:solid;
border-bottom-style:none;
border-left-style:none;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_10{
border-top-style:solid;
border-bottom-style:none;
border-left-style:solid;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_11{
border-top-style:solid;
border-bottom-style:none;
border-left-style:solid;
border-right-style:solid;
}

.entry_border_12{
border-top-style:solid;
border-bottom-style:solid;
border-left-style:none;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_13{
border-top-style:solid;
border-bottom-style:solid;
border-left-style:none;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}

.entry_border_14{
border-top-style:solid;
border-bottom-style:solid;
border-left-style:solid;
border-right-style:none;
padding-right:10px;
padding-left:10px;
}

.entry_border_15{
border-top-style:solid;
border-bottom-style:solid;
border-left-style:solid;
border-right-style:solid;
padding-right:10px;
padding-left:10px;
}
</style>

												<div
                                                                class="manual-page-content"
                                                                style="text-align:left; color:#000000;"
                                                            >

                                                                <?= $pageHtml ?>

                                                            </div>
												</td>
											</tr>
										</table>
										</td>
									</tr>
								</table>
								</td>
							</tr>
						</table>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		</table>
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
		</td>
	</tr>
</table>
<?php if ($carId !== false && $carId !== null): ?>
<script>
(function () {

    var carId = <?= json_encode((string)$carId) ?>;

    function addCarToIframeLinks(iframe) {

        try {

            var doc =
                iframe.contentDocument ||
                iframe.contentWindow.document;

            if (!doc) {
                return;
            }

            var links =
                doc.querySelectorAll(
                    'a[href*="/manual/view.php?"]'
                );

            links.forEach(function (link) {

                try {

                    var url =
                        new URL(
                            link.getAttribute('href'),
                            window.location.origin
                        );

                    /*
                     * Ha már van car paraméter,
                     * nem módosítjuk.
                     */
                    if (url.searchParams.has('car')) {
                        return;
                    }

                    /*
                     * Aktuális autó ID hozzáadása.
                     */
                    url.searchParams.set(
                        'car',
                        carId
                    );

                    /*
                     * Relatív URL visszaírása.
                     */
                    link.setAttribute(
                        'href',
                        url.pathname +
                        url.search +
                        url.hash
                    );

                } catch (e) {
                    /*
                     * Hibás vagy nem feldolgozható linket
                     * békén hagyunk.
                     */
                }

            });

        } catch (e) {
            /*
             * Ha az iframe tartalma nem hozzáférhető,
             * nem állítjuk meg az oldalt.
             */
        }
    }


    function processIframe(iframe) {

        /*
         * Már betöltött iframe.
         */
        addCarToIframeLinks(iframe);

        /*
         * Betöltés után is lefuttatjuk.
         */
        iframe.addEventListener(
            'load',
            function () {
                addCarToIframeLinks(iframe);
            }
        );

    }


    function init() {

        var iframes =
            document.querySelectorAll('iframe');

        iframes.forEach(function (iframe) {
            processIframe(iframe);
        });

    }


    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();

    }

})();
</script>
<?php endif; ?>
</body>
</html>
