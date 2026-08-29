<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

require_once __DIR__ . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

/*
 * Típus és oldal meghatározása
 *
 * Példák:
 * /7gen/epc/view.php?type=cn&page=ABC
 * /7gen/epc/view.php?type=clr&page=ABC
 */

$type = strtolower($_GET['type'] ?? '');
$page = strtoupper($_GET['page'] ?? '');

/*
 * Engedélyezett típusok
 */

$allowedTypes = ['cn1', 'clr'];

if (!in_array($type, $allowedTypes, true)) {
    die("Érvénytelen típus.");
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
     * Ellenőrizzük, hogy az autó valóban
     * ehhez az EPC típushoz tartozik-e.
     */

    if ($userCar !== null) {

        $userCarConfig = getCarConfig($userCar);

        if (
            $userCarConfig === null ||
            ($userCarConfig['epc_page'] ?? '') !== $type
        ) {
            $userCar = null;
            $userCarConfig = null;
        }
    }
}

/*
 * JSON betöltése
 */

$jsonFile = $configs[$type]['epc_json'];

$json = file_get_contents($jsonFile);

if ($json === false) {
    die("Nem találom a(z) " . $type . "-epc.json fájlt.");
}

$epc = json_decode($json, true);

if ($epc === null) {
    die("JSON hiba: " . json_last_error_msg());
}

/*
 * Képformátum típusonként
 */

$imageExtension = $configs[$type]['extension'];

/*
 * Kép fájljának meghatározása
 */

$imagePage = strtoupper($page);

if (substr_count($imagePage, '_') == 1) {
    $imageFile = $imagePage . "_." . $imageExtension;
} else {
    $imageFile = $imagePage . "." . $imageExtension;
}

$imagePath = $configs[$type]['image_dir'] . $imageFile;

/*
 * Kért kategória megkeresése
 */

$current = null;

foreach ($epc as $category) {

    if (($category['id'] ?? '') === $page) {
        $current = $category;
        break;
    }

}

if ($current === null) {
    echo "Page not found";
    exit;
}

/*
 * Keresési adat a vissza linkhez
 */

$search = $_GET['search'] ?? '';
$back = $_GET['back'] ?? '';
echo '<pre>';
var_dump($back);
echo '</pre>';
$from_cars = $_GET['from_cars'] ?? '';
?>
<html>
<head>
	<title>RichCars - EPC</title>
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
						<td class="text-right">
						</td>
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td align="left">
				<table style="width:100%;">
					<tr>
						<td style="padding:0px;text-align:center;">
    					<span class="epc-title">
    					<?php if ($userCar !== null): ?>
        				<?php if (trim((string)($userCar['name'] ?? '')) !== ''): ?>
            			<?= htmlspecialchars($userCar['name']) ?> - 
        				<?php endif; ?>
        				<?= htmlspecialchars($userCar['vin']) ?> - 
        				<?= htmlspecialchars(mb_convert_case($current['title'], MB_CASE_TITLE, 'UTF-8')) ?> - EPC
    					<?php else: ?>
        				<?= htmlspecialchars($configs[$type]['name']) ?> - <?= htmlspecialchars(mb_convert_case($current['title'], MB_CASE_TITLE, 'UTF-8')) ?> - EPC
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
								<th style="background-color: #bb271a;color:#ffffff; width:5%;" class="epc-text p5 pl10">Ref.</th>
								<th style="background-color: #bb271a;color:#ffffff; width:16%;" class="epc-text p5">Cikkszám</th>
								<th style="background-color: #bb271a;color:#ffffff;text-align:left;" class="epc-text p5">Megnevezés</th>
								<th style="background-color: #bb271a;color:#ffffff; width:4%;" class="epc-text p5 text-right pr10">Darab</th>
							</tr>
							<?php
							$i = 0;
							?>
							<?php foreach ($current['parts'] as $part): ?>
								<?php
								$rowClass = ($i % 2 == 0) ? 'row-even' : 'row-odd';
								?>
								<tr class="<?= $rowClass ?>">
									<td class="epc-text p5 pl10">
									<?= htmlspecialchars($part['ref']) ?>
									</td>
									<td class="epc-text p5">
									<?= htmlspecialchars($part['part_number']) ?>
									</td>
									<td class="epc-text p5" style="text-align:left;">
									<?= htmlspecialchars($part['name']) ?>
									</td>
									<td class="epc-text p5 text-center pr10">
									<?= !empty($part['qty']) ? htmlspecialchars($part['qty']) : '&nbsp;' ?>
									</td>
								</tr>
								<?php $i++; ?>
							<?php endforeach; ?>
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
</body>
</html>
