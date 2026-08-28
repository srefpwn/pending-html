<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once __DIR__ . '/functions.php';


/*
 * Manual típusa
 *
 * Példa:
 * /manual/?type=cn1&year=2006
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
 * Manual könyv típusa
 */

$book = strtolower(
    trim((string)($_GET['book'] ?? 'manual'))
);

if (!in_array($book, ['manual', 'body'], true)) {
    $book = 'manual';
}


/*
 * Van-e konkrét manual kiválasztva?
 */

$manualSelected =
    $type !== ''
    && $year !== false
    && $year !== null
    && $year > 0;


/*
 * Manual ellenőrzése
 */

$manualError = '';

if ($manualSelected) {

    $listaFile = getManualListFile(
        $type,
        $year,
        $book
    );

    if (!is_file($listaFile)) {
        $manualError =
            'A kiválasztott típushoz és évjárathoz nem található szerviz kézikönyv.';
    }
}


/*
 * Vissza URL
 */

$backUrl = '/';

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
						<td class="textv-top">
						<a href="<?= htmlspecialchars($returnUrl) ?>" class="leftmenu-back">Vissza</a><br>
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
						<?php if ($manualSelected && $manualError === ''): ?>
    					<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/list.php'; ?>
						<?php else: ?>
                        <table align="left" width="100%" class="table-border">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<?php if ($manualError !== ''): ?>
                       			<span class="epc-title"><?= htmlspecialchars($manualError) ?></span>
                       			<?php endif; ?>
                       			</td>
                  			</tr>
                  			<tr>
                       			<td style="height:220px;">
                       			</td>
                  			</tr>
                  		</table>
						<?php endif; ?>
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
</body>
</html>
