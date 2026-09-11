<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

/*
 * Bejelentkezés ellenőrzése
 */
$userId = requireLogin();


/*
 * Autó ID
 *
 * GET:
 * /service/add.php?car=1
 */
$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);


/*
 * POST esetén a car ID POST-ból is érkezhet
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postCarId = filter_input(
        INPUT_POST,
        'car_id',
        FILTER_VALIDATE_INT
    );

    if (
        $postCarId !== false &&
        $postCarId !== null
    ) {
        $carId = $postCarId;
    }
}


/*
 * Érvényes autó ID ellenőrzése
 */
if (
    $carId === false ||
    $carId === null ||
    $carId <= 0
) {
    denyAccess();
}


/*
 * Saját autó ellenőrzése
 */
$car = requireServiceCar($carId);


/*
 * Autó adatai a megjelenítéshez
 */
$carName = trim(
    (string)($car['name'] ?? '')
);

$vin = (string)(
    $car['vin'] ?? ''
);


/*
 * CSRF token
 */
$csrfToken = getServiceCsrfToken();


/*
 * Üzenet
 */
$message = '';
$messageType = '';

/*
 * Aktuális legmagasabb kilométeróra-állás
 */
$latestKm = null;

$serviceResult = loadServiceData(
    $userId,
    $carId
);

if ($serviceResult['success']) {

    $serviceData =
        $serviceResult['data'];

    foreach ($serviceData['entries'] as $serviceEntry) {

        if (
            isset($serviceEntry['km']) &&
            $serviceEntry['km'] !== null &&
            is_numeric($serviceEntry['km'])
        ) {

            $entryKm = (int)$serviceEntry['km'];

            if (
                $latestKm === null ||
                $entryKm > $latestKm
            ) {
                $latestKm = $entryKm;
            }
        }
    }
}
/*
 * POST feldolgozása
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF ellenőrzés
     */
    if (!verifyServiceCsrf()) {

        $message = 'Érvénytelen kérés.';
        $messageType = 'error';

    } else {

        /*
         * Alapadatok
         */
        $date = trim(
            (string)($_POST['date'] ?? '')
        );

        $kmRaw = trim(
            (string)($_POST['km'] ?? '')
        );

        $type = $_POST['type'] ?? 'simple';

        $title = trim(
            (string)($_POST['title'] ?? '')
        );

        $description = trim(
            (string)($_POST['description'] ?? '')
        );


        /*
         * Típus ellenőrzése
         */
        if (
            $type !== 'simple' &&
            $type !== 'detailed'
        ) {

            $message =
                'Érvénytelen rögzítési mód.';

            $messageType = 'error';

        }


        /*
         * Dátum ellenőrzése
         */
        if ($messageType !== 'error') {

            $dateObject = DateTime::createFromFormat(
                'Y-m-d',
                $date
            );

            $dateErrors =
                DateTime::getLastErrors();

            if (
                $dateObject === false ||
                (
                    $dateErrors !== false &&
                    (
                        $dateErrors['warning_count'] > 0 ||
                        $dateErrors['error_count'] > 0
                    )
                ) ||
                $dateObject->format('Y-m-d') !== $date
            ) {

                $message =
                    'A megadott dátum érvénytelen.';

                $messageType = 'error';

            } elseif (
                $date > date('Y-m-d')
            ) {

                $message =
                    'A szervizbejegyzés dátuma nem lehet jövőbeli.';

                $messageType = 'error';
            }
        }


        /*
         * Megnevezés ellenőrzése
         */
        if (
            $messageType !== 'error' &&
            $title === ''
        ) {

            $message =
                'A megnevezés megadása kötelező.';

            $messageType = 'error';
        }


        /*
         * Kilométer ellenőrzése
         */
        $km = null;

        if ($messageType !== 'error') {

            if ($kmRaw !== '') {

                if (
                    !ctype_digit($kmRaw)
                ) {

                    $message =
                        'A kilométeróra-állás csak egész szám lehet.';

                    $messageType = 'error';

                } else {

                    $km = (int)$kmRaw;

                    if ($km < 0) {

                        $message =
                            'A kilométeróra-állás nem lehet negatív.';

                        $messageType = 'error';
                    }
                }
            }
        }


/*
 * Szervizkönyv betöltése
 */
if ($messageType !== 'error') {

    $serviceResult = loadServiceData(
        $userId,
        $carId
    );


    /*
     * Hibás vagy sérült szervizkönyvet
     * nem kezelünk üresként.
     */
    if (
        !$serviceResult['success']
    ) {

        $message =
            $serviceResult['error'];

        $messageType = 'error';

    } else {

        $serviceData =
            $serviceResult['data'];

		
        /*
         * Dátum- és km-sorrend ellenőrzése
         */
        $orderValidation =
            validateServiceEntryOrder(
                $serviceData['entries'],
                $date,
                $km
            );


        if (
            !$orderValidation['success']
        ) {

            $message =
                $orderValidation['error'];

            $messageType = 'error';
        }
    }
}

/*
 * Bejegyzés előkészítése
 *
 * Az ID-t itt még nem generáljuk.
 * A véglegesítéskor a confirm.php
 * generálja az aktuális adatok alapján.
 */
if ($messageType !== 'error') {

    /*
     * Alap bejegyzés
     */
    $entry = [

        'date' => $date,

        'km' => $km,

        'type' => $type,

        'title' => $title,

        'description' => $description,

        'items' => [],

        'labor_cost' => 0,

        'total_cost' => 0

    ];


            /*
             * Egyszerű rögzítés
             */
            if ($type === 'simple') {

                $totalCostRaw = trim(
                    (string)($_POST['total_cost'] ?? '')
                );


                if ($totalCostRaw === '') {

                    $totalCost = 0;

                } else {

                    /*
                     * Magyar számformátum támogatása
                     */
                    $normalizedCost = str_replace(
                        [' ', ','],
                        ['', '.'],
                        $totalCostRaw
                    );


                    if (
                        !is_numeric($normalizedCost) ||
                        (float)$normalizedCost < 0
                    ) {

                        $message =
                            'A megadott teljes költség érvénytelen.';

                        $messageType = 'error';

                    } else {

                        $totalCost = (float)$normalizedCost;

                        if (
                            floor($totalCost) === $totalCost
                        ) {
                            $totalCost = (int)$totalCost;
                        }
                    }
                }


                if ($messageType !== 'error') {

                    $entry['total_cost'] =
                        $totalCost;
                }
            }


            /*
             * Tételes rögzítés
             */
            if (
                $type === 'detailed' &&
                $messageType !== 'error'
            ) {

                $partNumbers =
                    $_POST['item_part_number'] ?? [];

                $itemNames =
                    $_POST['item_name'] ?? [];

                $quantities =
                    $_POST['item_quantity'] ?? [];

                $unitPrices =
                    $_POST['item_unit_price'] ?? [];


                $items = [];


                /*
                 * Alkatrészsorok feldolgozása
                 */
                $rowCount = max(
                    count($partNumbers),
                    count($itemNames),
                    count($quantities),
                    count($unitPrices)
                );


                for (
                    $i = 0;
                    $i < $rowCount;
                    $i++
                ) {

                    $partNumber = trim(
                        (string)($partNumbers[$i] ?? '')
                    );

                    $itemName = trim(
                        (string)($itemNames[$i] ?? '')
                    );

                    $quantityRaw = trim(
                        (string)($quantities[$i] ?? '')
                    );

                    $unitPriceRaw = trim(
                        (string)($unitPrices[$i] ?? '')
                    );


                    /*
                     * Teljesen üres sor kihagyása
                     */
                    if (
                        $partNumber === '' &&
                        $itemName === '' &&
                        $quantityRaw === '' &&
                        $unitPriceRaw === ''
                    ) {
                        continue;
                    }


                    /*
                     * Alkatrész neve kötelező
                     */
                    if ($itemName === '') {

                        $message =
                            'Minden megadott alkatrészhez meg kell adni a megnevezést.';

                        $messageType = 'error';

                        break;
                    }


                    /*
                     * Mennyiség
                     */
                    if (
                        $quantityRaw === '' ||
                        !is_numeric($quantityRaw) ||
                        (float)$quantityRaw <= 0
                    ) {

                        $message =
                            'Az alkatrész mennyisége érvénytelen.';

                        $messageType = 'error';

                        break;
                    }


                    /*
                     * Egységár
                     */
                    if (
                        $unitPriceRaw === '' ||
                        !is_numeric($unitPriceRaw) ||
                        (float)$unitPriceRaw < 0
                    ) {

                        $message =
                            'Az alkatrész egységára érvénytelen.';

                        $messageType = 'error';

                        break;
                    }


                    $quantity =
                        (float)$quantityRaw;

                    $unitPrice =
                        (float)$unitPriceRaw;


                    /*
                     * Egész értékek tisztítása
                     */
                    if (
                        floor($quantity) === $quantity
                    ) {
                        $quantity = (int)$quantity;
                    }

                    if (
                        floor($unitPrice) === $unitPrice
                    ) {
                        $unitPrice = (int)$unitPrice;
                    }


                    $items[] = [

    'part_number' =>
        $partNumber,

    'name' =>
        $itemName,

    'quantity' =>
        $quantity,

    'unit_price' =>
        $unitPrice,

    'total_price' =>
        $quantity * $unitPrice

];
                }


                /*
                 * Munkadíj
                 */
                if ($messageType !== 'error') {

                    $laborCostRaw = trim(
                        (string)($_POST['labor_cost'] ?? '')
                    );


                    if ($laborCostRaw === '') {

                        $laborCost = 0;

                    } else {

                        $normalizedLabor =
                            str_replace(
                                [' ', ','],
                                ['', '.'],
                                $laborCostRaw
                            );


                        if (
                            !is_numeric($normalizedLabor) ||
                            (float)$normalizedLabor < 0
                        ) {

                            $message =
                                'A munkadíj összege érvénytelen.';

                            $messageType = 'error';

                        } else {

                            $laborCost =
                                (float)$normalizedLabor;
                        }
                    }
                }


                /*
                 * Tételes összeg kiszámítása
                 */
                if ($messageType !== 'error') {

                    $partsTotal = 0;

                    foreach ($items as $item) {

$partsTotal +=
    (float)$item['total_price'];
                    }


                    $totalCost =
                        $partsTotal +
                        $laborCost;


                    if (
                        floor($totalCost) === $totalCost
                    ) {
                        $totalCost =
                            (int)$totalCost;
                    }


                    $entry['items'] =
                        $items;

                    $entry['labor_cost'] =
                        $laborCost;

                    $entry['total_cost'] =
                        $totalCost;
                }
            }


            /*
             * Ha minden rendben van,
             * ideiglenesen SESSION-be tesszük.
             *
             * FONTOS:
             * Itt még NEM írunk JSON-t.
             */
            if ($messageType !== 'error') {

$_SESSION['service_pending_entry'] = [

    'user_id' =>
        $userId,

    'car_id' =>
        $carId,

    'entry' =>
        $entry,

    'form' => $_POST

];


                /*
                 * Átirányítás az ellenőrző oldalra
                 */
                header(
                    'Location: /service/confirm.php?car='
                    . (int)$carId
                );

                exit;
            }
        }
    }
}
/*
 * Jelzi, hogy az űrlapot egy korábbi
 * confirm.php oldalról töltjük vissza.
 */
$restoredFromPending = false;


/*
 * Ha a confirm.php oldalról visszatérünk,
 * a korábban elküldött űrlap adatait
 * visszatöltjük.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_SESSION['service_pending_entry']) &&
    is_array($_SESSION['service_pending_entry']) &&
    (int)($_SESSION['service_pending_entry']['car_id'] ?? 0) === (int)$carId &&
    isset($_SESSION['service_pending_entry']['form']) &&
    is_array($_SESSION['service_pending_entry']['form'])
) {

    $_POST =
        $_SESSION['service_pending_entry']['form'];

    $restoredFromPending = true;

}

/*
 * Alkatrészsorok megjelenítéséhez
 */
$formItems = [];

if (
    isset($_POST['item_part_number']) &&
    is_array($_POST['item_part_number'])
) {

    $partNumbers =
        $_POST['item_part_number'];

    $itemNames =
        $_POST['item_name'] ?? [];

    $quantities =
        $_POST['item_quantity'] ?? [];

    $unitPrices =
        $_POST['item_unit_price'] ?? [];


    $formRowCount =
        count($partNumbers);


    for (
        $i = 0;
        $i < $formRowCount;
        $i++
    ) {

        $formItems[] = [

            'part_number' =>
                (string)($partNumbers[$i] ?? ''),

            'name' =>
                (string)($itemNames[$i] ?? ''),

            'quantity' =>
                (string)($quantities[$i] ?? ''),

            'unit_price' =>
                (string)($unitPrices[$i] ?? '')

        ];
    }
}


/*
 * Ha nincs visszatöltendő sor,
 * akkor legalább egy üres sort megjelenítünk.
 */
if (empty($formItems)) {

    $formItems[] = [

        'part_number' => '',
        'name' => '',
        'quantity' => '1',
        'unit_price' => ''

    ];
}
?>
<html>
<head>
    <title>RichCars - Új bejegyzés - Szervizkönyv</title>
		<meta charset="UTF-8">
		<link rel="icon" href="/favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    	<link href="/css.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="/scripts/jquery.min.js"></script>
		<script type="text/javascript" src="/scripts/jquery.timers-1.1.2.js"></script>
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
                                    <table align="left" width="100%" class="table-border">
                                        <tr>
                                            <td class="textv-top" style="padding-bottom:20px;">
                                                <table align="center" class="table-border" width="100%">
                                                    <!-- Cím -->
                                                    <tr>
                                                        <td style="padding:0px;text-align:center;">
                                                            <span class="epc-title"><?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?> - <?php endif; ?><?= htmlspecialchars($vin) ?> - Új bejegyzés - Szervizkönyv</span>
                                                        </td>
                                                    </tr>
                                                    <!-- Üzenet -->
                                                    <?php if ($message !== ''): ?>
                                                    <tr>
                                                        <td style="padding:20px;text-align:center;">
                                                            <?php if ($messageType === 'success'): ?>
                                                                <span style="color:green;"><?= htmlspecialchars($message) ?></span>
                                                            <?php else: ?>
                                                                <span style="color:red;"><?= htmlspecialchars($message) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <!-- Űrlap -->
                                                    <tr>
														<td style=" padding:20px; text-align:center;">
														<form method="post">
														<input type="hidden" name="car_id" value="<?= (int)$carId ?>">
														<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
														<table align="center" class="pb20">
															<!-- Rögzítés módja -->
															<tr>
																<td class="pr5 epc-text">Rögzítés módja:
																</td>
																<td>
																<table class="table-border text-left">
																	<tr>
																		<label>
																		<td style="vertical-align:middle;"><input type="radio" name="type" value="simple" <?= ( ($_POST['type'] ?? 'simple') === 'simple') ? 'checked' : '' ?> onchange="toggleServiceType()">
																		</td>
																		<td style="padding-left:5px;"><span class="epc-text">Egyszerűsített</span>
																		</td>
																		</label>
																		<label>
																		<td style="padding-left:5px;"><input type="radio" name="type" value="detailed" <?= ( ($_POST['type'] ?? '') === 'detailed') ? 'checked' : '' ?> onchange="toggleServiceType()">
																		</td>
																		<td style="padding-left:5px;"><span class="epc-text">Tételes</span>
																		</td>
																		</label>
																	</tr>
																</table>
																</td>
															</tr>
														</table>
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:400px;text-align:center;" class="p10">Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;width:100%;" class="table-border">
															<!-- Dátum -->
															<tr>
																<td style="width:50%;" class="epc-text row-even p5">Dátum:
																</td>
																<td style="width:50%;" class="row-even p5">
																<span class="select-wrapper2"><input type="date" name="date" value="<?= htmlspecialchars( $_POST['date'] ?? date('Y-m-d')) ?>" required></span>
																</td>
															</tr>
															<!-- Kilométer -->
															<tr>
																<td class="pr5 epc-text row-odd p5">Kilométer:
																</td>
																<td class="row-odd p5">
																<input type="number" name="km" min="0" step="1" value="<?= htmlspecialchars( $_POST['km'] ?? '') ?>" placeholder="<?= $latestKm !== null ? htmlspecialchars((string)$latestKm) : '' ?>">
																</td>
															</tr>
															<!-- Megnevezés -->
															<tr>
																<td class="pr5 epc-text row-even p5"> Kategória:
																</td>
																<td class="row-even p5">
																<span class="select-wrapper2">
                                                                <select name="title" required>
                                                                   	<option value="Szerviz" <?= (($_POST['title'] ?? 'Szerviz') === 'Szerviz') ? 'selected' : '' ?>>Szerviz</option>
    																<option value="Alkatrész" <?= (($_POST['title'] ?? '') === 'Alkatrész') ? 'selected' : '' ?>>Alkatrész</option>
                                                                </select>
                                                                </span>
																</td>
															</tr>
															<!-- Leírás -->
															<tr>
																<td class="pr5 epc-text row-odd p5">Leírás:
																</td>
																<td class="row-odd p5">
																<textarea name="description" rows="5" style="width:100%;box-sizing:border-box;"><?= htmlspecialchars( $_POST['description'] ?? '') ?></textarea>
																</td>
															</tr>
															<!-- Egyszerű költség -->
															<tr id="simple-cost-row">
																<td class="pr5 epc-text row-even p5">Teljes költség:
																</td>
																<td class="row-even p5">
																<input type="number" name="total_cost" min="0" step="0.01" placeholder="Ft" value="<?= htmlspecialchars(  $_POST['total_cost'] ?? '') ?>">
																</td>
															</tr>
														</table>
														<!-- Tételes rész -->
														<div id="detailed-section" style="display:none;">
														<table id="items-table" align="center" class="servicestable2 table-border" style="width:100%;">
    														<tr>
        														<td class="epc-text p5" style="background-color:#bb271a;color:#ffffff;width:18%;">Cikkszám
        														</td>
        														<td class="epc-text p5" style="background-color:#bb271a;color:#ffffff;width:35%;">Megnevezés
        														</td>
        														<td class="epc-text p5" style="background-color:#bb271a;color:#ffffff;width:13%;">Egységár
       															</td>
        														<td class="epc-text p5" style="background-color:#bb271a;color:#ffffff;width:7%;">Darab
        														</td>
        														<td class="epc-text p5" style="background-color:#bb271a;color:#ffffff;width:13%;">Összesen
       															</td>
        														<td style="background-color:#bb271a;color:#ffffff;width:100px;">
        														</td>
    														</tr>
<?php foreach ($formItems as $index => $formItem): ?>
<?php
$rowClass =
    ($index % 2 === 0)
        ? 'row-even'
        : 'row-odd';
?>
    <tr class="service-item <?= $rowClass ?>">
        <td class="p5">
        <input type="text" name="item_part_number[]" value="<?= htmlspecialchars($formItem['part_number']) ?>">
        </td>
        <td class="p5">
        <input type="text" name="item_name[]" value="<?= htmlspecialchars($formItem['name']) ?>">
        </td>
        <td class="p5">
        <input type="number" name="item_unit_price[]" min="0" step="0.01" value="<?= htmlspecialchars($formItem['unit_price']) ?>" >
        </td>
        <td class="p5">
        <input type="number" name="item_quantity[]"  min="0.01" step="0.01" value="<?= htmlspecialchars($formItem['quantity']) ?>">
        </td>
        <td class="p5">
    	<input type="number" class="item-total-price" readonly>
		</td>
		<td class="p5 textv-center">
   		<?php if ($index > 0): ?>
        <button type="button" class="service-item-delete" onclick="removeServiceItem(this)"> − Törlés
        </button>
    	<?php endif; ?>
		</td>
    </tr>
<?php endforeach; ?>

</table>
														<table align="center" class="table-border" style="margin-top:5px;">
    														<tr>
        														<td class="textv-center p5">
            													<button type="button" onclick="addServiceItem()"> + Alkatrész hozzáadása
            													</button>
        														</td>
    														</tr>
														</table>
														</td>
													</tr>
												</table>
												</div>
												<div id="labor-cost-section">
												<table class="table-border text-center" width="100%">
                                                    <tr>
                                                    	<td style="padding:20px;text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:50%;text-align:center;" class="p10">Munkadíj
																</td>
															</tr>
														</table>
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td class="pr5 row-even p5 epc-text" width="100">Munkadíj:
																</td>
																<td class="row-even p5" style="width:50%;">
																<input type="number" name="labor_cost" min="0" step="0.01" placeholder="Ft" value="<?= htmlspecialchars( $_POST['labor_cost'] ?? '' ) ?>">
																</td>
															</tr>
														</table>
														</td>
													</tr>
												</table>
												</div>
												<table class="table-border text-center pb20">
													<tr>
														<td>
														<button type="submit">Szervizbejegyzés mentése
														</button>
														</form>
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
</table><script>

function toggleServiceType()
{
    const selected =
        document.querySelector(
            'input[name="type"]:checked'
        );

    if (!selected) {
        return;
    }


    const detailed =
        selected.value === 'detailed';


    const detailedSection =
        document.getElementById(
            'detailed-section'
        );


    const simpleCostRow =
        document.getElementById(
            'simple-cost-row'
        );

	const laborCostSection =
    	document.getElementById(
        'labor-cost-section'
   		);

    if (detailed) {

        detailedSection.style.display = '';

        simpleCostRow.style.display = 'none';
        
    	laborCostSection.style.display = '';

    } else {

        detailedSection.style.display = 'none';

        simpleCostRow.style.display = '';

    	laborCostSection.style.display = 'none';
    }
}


/*
 * Új alkatrészsor hozzáadása.
 */
function addServiceItem()
{
    const table =
        document.getElementById(
            'items-table'
        );


    /*
     * Új alkatrészsor létrehozása.
     */
    const row =
        document.createElement('tr');


    row.className =
        'service-item';


    /*
     * Meglévő alkatrészsorok száma.
     */
    const itemRows =
        table.querySelectorAll(
            'tr.service-item'
        );


    const newIndex =
        itemRows.length + 1;


    /*
     * Háttérszín.
     */
    if (newIndex % 2 === 1) {

        row.classList.add(
            'row-even'
        );

    } else {

        row.classList.add(
            'row-odd'
        );

    }


    /*
     * Új sor tartalma.
     *
     * Mivel ez nem az első sor,
     * ezért kap törlés gombot.
     */
    row.innerHTML = `
        <td class="p5">
            <input
                type="text"
                name="item_part_number[]"
            >
        </td>

        <td class="p5">
            <input
                type="text"
                name="item_name[]"
            >
        </td>

        <td class="p5">
            <input
                type="number"
                name="item_quantity[]"
                min="0.01"
                step="0.01"
                value="1"
            >
        </td>

        <td class="p5">
            <input
                type="number"
                name="item_unit_price[]"
                min="0"
                step="0.01"
            >
        </td>
        <td class="p5">
    <input
        type="number"
        class="item-total-price"
        readonly
    >
</td>

        <td class="p5 textv-center">
            <button
                type="button"
                class="service-item-delete"
                onclick="removeServiceItem(this)"
            >
                − Törlés
            </button>
        </td>
    `;


    /*
     * Új sor hozzáadása.
     */
    table.appendChild(row);


    /*
     * Sorok frissítése.
     */
    updateServiceItemRows();
}


/*
 * Alkatrészsor törlése.
 */
function removeServiceItem(button)
{
    const row =
        button.closest(
            'tr.service-item'
        );


    if (!row) {
        return;
    }


    /*
     * Sor törlése.
     */
    row.remove();


    /*
     * Sorok újrarendezése.
     */
    updateServiceItemRows();


    /*
     * Ha minden sort töröltünk,
     * létrehozunk egy új üres első sort.
     */
    const table =
        document.getElementById(
            'items-table'
        );


    const remainingRows =
        table.querySelectorAll(
            'tr.service-item'
        );


    if (remainingRows.length === 0) {

        addServiceItem();

    }
}


/*
 * Alkatrészsorok frissítése.
 *
 * - háttérszínek
 * - törlés gombok
 *
 * Az első sorban SOHA nincs
 * törlés gomb.
 */
function updateServiceItemRows()
{
    const table =
        document.getElementById(
            'items-table'
        );


    const allRows =
        table.querySelectorAll(
            'tr.service-item'
        );


    allRows.forEach(
        function (itemRow, index) {

            /*
             * Régi háttérszínek eltávolítása.
             */
            itemRow.classList.remove(
                'row-odd',
                'row-even'
            );


            /*
             * Háttérszín beállítása.
             */
            if (index % 2 === 0) {

                itemRow.classList.add(
                    'row-even'
                );

            } else {

                itemRow.classList.add(
                    'row-odd'
                );

            }


            /*
             * Meglévő törlés gomb keresése.
             */
            const deleteButton =
                itemRow.querySelector(
                    '.service-item-delete'
                );


            /*
             * Első sor:
             *
             * nincs törlés gomb.
             */
            if (index === 0) {

                if (deleteButton) {

                    const deleteCell =
                        deleteButton.closest('td');


                    if (deleteCell) {

                        deleteCell.remove();

                    }

                }

                return;
            }


            /*
             * Minden további sor:
             *
             * legyen törlés gomb.
             */
            if (!deleteButton) {

                const cell =
                    document.createElement(
                        'td'
                    );


                cell.className =
                    'p5 textv-center';


                cell.innerHTML = `
                    <button
                        type="button"
                        class="service-item-delete"
                        onclick="removeServiceItem(this)"
                    >
                        − Törlés
                    </button>
                `;


                itemRow.appendChild(cell);
            }

        }
    );
}


/*
 * Oldal betöltésekor beállítjuk
 * a rögzítés módját és a sorokat.
 */
document.addEventListener(
    'DOMContentLoaded',
    function () {

        toggleServiceType();

        updateServiceItemRows();

        updateAllServiceItemTotals();

    }
);
function updateServiceItemTotal(row)
{
    const quantityInput =
        row.querySelector(
            'input[name="item_quantity[]"]'
        );

    const unitPriceInput =
        row.querySelector(
            'input[name="item_unit_price[]"]'
        );

    const totalInput =
        row.querySelector(
            '.item-total-price'
        );

    if (
        !quantityInput ||
        !unitPriceInput ||
        !totalInput
    ) {
        return;
    }

    const quantity =
        parseFloat(quantityInput.value) || 0;

    const unitPrice =
        parseFloat(unitPriceInput.value) || 0;

    const total =
        quantity * unitPrice;

    totalInput.value =
        total > 0
            ? total
            : '';
}


function updateAllServiceItemTotals()
{
    const rows =
        document.querySelectorAll(
            'tr.service-item'
        );

    rows.forEach(
        function (row) {
            updateServiceItemTotal(row);
        }
    );
}


document.addEventListener(
    'input',
    function (event) {

        if (
            event.target.matches(
                'input[name="item_quantity[]"], input[name="item_unit_price[]"]'
            )
        ) {
            const row =
                event.target.closest(
                    'tr.service-item'
                );

            if (row) {
                updateServiceItemTotal(row);
            }
        }

    }
);

</script>
</body>
</html>
