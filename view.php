<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

/*
 * Bejelentkezés
 */
$userId = requireLogin();


/*
 * Autó ID
 */
$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

if (
    $carId === false ||
    $carId === null ||
    $carId <= 0
) {
    denyAccess();
}


/*
 * Autó jogosultság
 */
$car = requireServiceCar($carId);


/*
 * Bejegyzés ID
 */
$entryId = filter_input(
    INPUT_GET,
    'entry',
    FILTER_VALIDATE_INT
);

if (
    $entryId === false ||
    $entryId === null ||
    $entryId <= 0
) {
    denyAccess();
}


/*
 * Saját oldalra való visszatérési cím
 */
if (empty($_GET['back'])) {

    $_GET['back'] =
        '/service/view.php?car=' .
        urlencode($carId) .
        '&entry=' .
        urlencode($entryId);
}


/*
 * Navigáció
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

/*
 * Szervizadatok betöltése
 */
$serviceResult = loadServiceData(
    $userId,
    $carId
);


/*
 * Sérült szervizkönyv
 */
if (!$serviceResult['success']) {

    http_response_code(500);

    $serviceError =
        $serviceResult['error']
        ?? 'A szervizkönyv nem tölthető be.';

    require __DIR__ . '/service_error.php';

    exit;
}


$serviceData =
    $serviceResult['data'];


/*
 * Bejegyzés keresése
 */
$entry = null;

foreach (
    $serviceData['entries']
    as $item
) {

    if (
        isset($item['id']) &&
        (int)$item['id'] === $entryId
    ) {

        $entry = $item;

        break;
    }
}


/*
 * Ha nincs ilyen bejegyzés
 */
if ($entry === null) {
    denyAccess();
}


/*
 * Autó adatai
 */
$carName = trim(
    (string)($car['name'] ?? '')
);

$vin = (string)(
    $car['vin'] ?? ''
);

/*
 * EPC konfiguráció meghatározása VIN alapján
 */
$epcPage = null;
$epcPartNumbers = [];

$epcEnabled = false;

if (
    $vin !== '' &&
    isset($vin_configs[$vin])
) {
    $epcPage =
        $vin_configs[$vin]['epc_page']
        ?? null;

    $epcEnabled =
        (int)($vin_configs[$vin]['epc_enable'] ?? 0) === 1;
}


/*
 * EPC JSON betöltése
 */
if (
    $epcEnabled &&
    $epcPage !== null &&
    isset($configs[$epcPage]['epc_json'])
) {

    $epcJsonFile =
        $configs[$epcPage]['epc_json'];

    if (is_file($epcJsonFile)) {

        $epcJson =
            file_get_contents($epcJsonFile);

        $epcData =
            json_decode(
                $epcJson,
                true
            );

        /*
         * Cikkszámok kigyűjtése
         */
        if (is_array($epcData)) {

            foreach ($epcData as $category) {

                if (
                    !isset($category['parts']) ||
                    !is_array($category['parts'])
                ) {
                    continue;
                }

                foreach ($category['parts'] as $part) {

                    if (
                        isset($part['part_number']) &&
                        $part['part_number'] !== ''
                    ) {

                        $epcPartNumbers[
                            (string)$part['part_number']
                        ] = true;
                    }
                }
            }
        }
    }
}
/*
 * Bejegyzés adatai
 */
$date = trim(
    (string)($entry['date'] ?? '')
);

$km = $entry['km'] ?? '';

$type = (string)(
    $entry['type'] ?? 'simple'
);

$title = trim(
    (string)(
        $entry['title']
        ?? 'Szervizbejegyzés'
    )
);

$description = trim(
    (string)(
        $entry['description']
        ?? ''
    )
);

$totalCost =
    $entry['total_cost'] ?? 0;

$laborCost =
    $entry['labor_cost'] ?? 0;


$items = (
    isset($entry['items']) &&
    is_array($entry['items'])
)
    ? $entry['items']
    : [];


/*
 * Költség formázása
 */
function formatServicePrice(
    $value
): string {

    if (!is_numeric($value)) {
        return '0 Ft';
    }

    return number_format(
        (float)$value,
        0,
        ',',
        ' '
    ) . ' Ft';
}

?>

<html>
<head>
    <title>RichCars - Szervizkönyv</title>
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
                                                <table align="center" class="table-border" width="100%">
                                                    <!-- Cím -->
                                                    <tr>
                                                        <td style="padding:0px;text-align:center;">
                                                        <span class="epc-title">
                                                        <?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?> - <?php endif; ?><?= htmlspecialchars($vin) ?> - <?= htmlspecialchars( date('Y.m.d.', strtotime($entry['date'] ?? ''))) ?> - Szervizkönyv
														</span>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px; text-align:center;">
														<!-- Alapadatok -->
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<!-- Dátum -->
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Dátum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10"><?= htmlspecialchars( date('Y.m.d.', strtotime($entry['date'] ?? ''))) ?>
																</td>
															</tr>
															<!-- Kilométer -->
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Kilométer:
																</td>
																<td class="epc-text row-odd p5 text-right pr10"><?php if ($km !== ''): ?><?= number_format( (int)$km, 0, ',', ' ') ?> km<?php else: ?> Nincs megadva<?php endif; ?>
																</td>
															</tr>
															<!-- Megnevezés -->
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Kategória:
																</td>
																<td class="epc-text row-even p5 text-right pr10"><?= htmlspecialchars($title) ?>
																</td>
															</tr>
															<!-- Megjegyzés -->
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Megjegyzés:
																</td>
																<td class="epc-text row-odd p5 text-right pr10"><?php if ($description !== ''): ?><?= nl2br( htmlspecialchars($description)) ?><?php endif; ?>
																</td>
															</tr>
														<?php if ($type === 'simple'): ?>
															<tr>
																<td width="200" class="pr5 epc-text p5 pl10" style="background-color:#444444;color:#ffffff;">Teljes költség:
																</td>
																<td width="190" class="epc-text p5 text-right pr10" style="background-color:#444444;color:#ffffff;"><?= formatServicePrice($totalCost) ?>
																</td>
															</tr>
														<?php endif; ?>
														</table>
														<?php if ($type === 'detailed'): ?>
														<!-- Alkatrészek -->
														<table align="center" style="width:100%;text-align:center;" class="table-border servicestable2">											
															<tr>
																<td class="pl10 epc-text row-odd p5 text-left" width="13%" style="background-color:#bb271a;color:#ffffff;">Cikkszám
																</td>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
																</td>
																<td class="pr5 epc-text row-odd p5" width="5%" style="background-color:#bb271a;color:#ffffff;">Darab
																</td>
																<td class="pr10 epc-text row-odd p5 text-right" width="10%" style="background-color:#bb271a;color:#ffffff;">Egységár
																</td>
																<td class="pr10 epc-text row-odd p5 text-right" width="10%" style="background-color:#bb271a;color:#ffffff;">Összesen
																</td>
															</tr>
<?php foreach ($items as $index => $item): ?>
<?php

$rowClass =
    ($index % 2 === 0)
        ? 'row-even'
        : 'row-odd';

$partNumber =
    (string)(
        $item['part_number'] ?? ''
    );

$itemName =
    (string)(
        $item['name'] ?? ''
    );

$quantity =
    (float)(
        $item['quantity'] ?? 0
    );

$unitPrice =
    (float)(
        $item['unit_price'] ?? 0
    );

$itemTotal =
    isset($item['total_price']) &&
    is_numeric($item['total_price'])
        ? (float)$item['total_price']
        : $quantity * $unitPrice;

?>
															<tr>
																<td class="pl10 epc-text <?= $rowClass ?> p5 text-left">
																<?php if (isset($epcPartNumbers[$partNumber])): ?><a href="/epc/search.php?type=<?= urlencode($epcPage) ?>&part_number=<?= urlencode($partNumber) ?><?= $navigationParams ?>"><?= htmlspecialchars($partNumber) ?></a><?php else: ?><?= htmlspecialchars($partNumber) ?><?php endif; ?></td>
																<td class="pl10 epc-text <?= $rowClass ?> p5 text-left"><?= htmlspecialchars($itemName) ?>
																</td>
																<td class="pr5 epc-text <?= $rowClass ?> p5"><?= htmlspecialchars( (string)$quantity) ?>
																</td>
																<td class="pr10 epc-text <?= $rowClass ?> p5 text-right"><?= formatServicePrice($unitPrice) ?>
																</td>
																<td class="pr10 epc-text <?= $rowClass ?> p5 text-right"><?= formatServicePrice($itemTotal) ?>
																</td>
															</tr>
														<?php endforeach; ?>
														</table>
														<?php if (empty($items)): ?>
														<table align="center" class="table-border">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:400px;text-align:center;" class="p10">Nincs rögzített alkatrész.
																</td>
															</tr>
														</table>
														<?php endif; ?>
														<!-- Munkadíj -->
														<table align="center" style="width:100%;text-align:center;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border servicestable2">	
															<tr>
																<td class="pr5 epc-text row-odd p5 text-left" style="padding-left:10px;background-color:#aaaaaa;">Munkadíj
																</td>
																<td width="300" class="epc-text row-odd p5 text-right" style="padding-right:10px;background-color:#aaaaaa;"><?= formatServicePrice($laborCost) ?>
																</td>
															</tr>
															<!-- Teljes költség -->
															<tr>
																<td class="pr5 epc-text row-odd p5 text-left" style="padding-left:10px;background-color:#444444;color:#ffffff;">Teljes költség
																</td>
																<td width="300" class="epc-text row-odd p5 text-right" style="padding-right:10px;background-color:#444444;color:#ffffff;"><?= formatServicePrice($totalCost) ?>
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
</body>
</html>
