<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/functions.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

/*
 * Bejelentkezés ellenőrzése
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


/*
 * Érvényes autó ID
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
 * Függőben lévő bejegyzés ellenőrzése
 *
 * Az add.php ezt a sessionbe menti:
 *
 * service_pending_entry
 * ├── user_id
 * ├── car_id
 * └── entry
 */
if (
    !isset($_SESSION['service_pending_entry']) ||
    !is_array($_SESSION['service_pending_entry'])
) {

    header(
        'Location: /service/add.php?car='
        . (int)$carId
    );

    exit;
}


/*
 * Függőben lévő adatok
 */
$pending =
    $_SESSION['service_pending_entry'];


/*
 * Felhasználó ellenőrzése
 *
 * A sessionben lévő bejegyzés csak annak
 * a felhasználónak használható fel,
 * aki létrehozta.
 */
if (
    !isset($pending['user_id']) ||
    (string)$pending['user_id'] !== (string)$userId
) {

    unset(
        $_SESSION['service_pending_entry']
    );

    denyAccess();
}


/*
 * Autó ID ellenőrzése
 */
if (
    !isset($pending['car_id']) ||
    (int)$pending['car_id'] !== $carId
) {

    unset(
        $_SESSION['service_pending_entry']
    );

    denyAccess();
}


/*
 * Bejegyzés ellenőrzése
 */
if (
    !isset($pending['entry']) ||
    !is_array($pending['entry'])
) {

    unset(
        $_SESSION['service_pending_entry']
    );

    denyAccess();
}


/*
 * A tényleges függőben lévő bejegyzés
 */
$entryData =
    $pending['entry'];



/*
 * CSRF token
 */
$csrfToken =
    getServiceCsrfToken();


/*
 * Üzenet
 */
$message = '';
$messageType = '';


/*
 * Véglegesítés
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF ellenőrzés
     */
    if (!verifyServiceCsrf()) {

        $message =
            'Érvénytelen kérés.';

        $messageType = 'error';

    /*
     * Művelet ellenőrzése
     */
    } elseif (
        !isset($_POST['action']) ||
        $_POST['action'] !== 'confirm'
    ) {

        $message =
            'Érvénytelen művelet.';

        $messageType = 'error';

    } else {

        /*
         * A szervizkönyv aktuális
         * állapotának betöltése.
         *
         * FONTOS:
         *
         * Ha a JSON hibás vagy sérült,
         * nem kezeljük üres szervizkönyvként.
         */
        $serviceResult =
            loadServiceData(
                $userId,
                $carId
            );


        /*
         * Betöltési hiba
         */
        if (
            !$serviceResult['success']
        ) {

            $message =
                $serviceResult['error']
                ?? 'A szervizkönyv nem tölthető be.';

            $messageType = 'error';

        } else {

            /*
             * Aktuális szervizkönyv
             */
            $serviceData =
                $serviceResult['data'];


            /*
             * Bejegyzés dátuma
             */
            $entryDate =
                trim(
                    (string)(
                        $entryData['date'] ?? ''
                    )
                );


            /*
             * Bejegyzés kilométeróra-állása
             */
            $entryKm = null;

            if (
                isset($entryData['km']) &&
                $entryData['km'] !== ''
            ) {

                $entryKm =
                    (int)$entryData['km'];
            }


            /*
             * Dátum- és kilométer-sorrend
             * ismételt ellenőrzése.
             *
             * Erre azért van szükség,
             * mert az add.php és a confirm.php
             * között az aktuális JSON változhatott.
             */
            $orderValidation =
                validateServiceEntryOrder(
                    $serviceData['entries'],
                    $entryDate,
                    $entryKm
                );


            /*
             * Sorrendi hiba
             */
            if (
                !$orderValidation['success']
            ) {

                $message =
                    $orderValidation['error'];

                $messageType = 'error';

            } else {

                /*
                 * Új ID generálása.
                 *
                 * Az ID-t nem a sessionből
                 * és nem a böngészőtől vesszük.
                 */
                $entryData['id'] =
                    getNextServiceEntryId(
                        $serviceData['entries']
                    );


                /*
                 * Bejegyzés hozzáadása
                 */
                $serviceData['entries'][] =
                    $entryData;


                /*
                 * Végleges mentés.
                 *
                 * A saveServiceData()
                 * ismételten ellenőrzi a teljes
                 * szervizkönyv struktúráját.
                 */
                if (
                    saveServiceData(
                        $userId,
                        $carId,
                        $serviceData
                    )
                ) {

                    /*
                     * Sikeres mentés után
                     * a pending adat törölhető.
                     */
                    unset(
                        $_SESSION['service_pending_entry']
                    );


                    /*
                     * Vissza a szervizkönyvhöz
                     */
                    header(
                        'Location: /service/?car='
                        . (int)$carId
                    );

                    exit;

                } else {

                    $message =
                        'A szervizbejegyzés mentése sikertelen.';

                    $messageType = 'error';
                }
            }
        }
    }
}


/*
 * Autó adatai
 */
$carName = trim(
    (string)(
        $car['name'] ?? ''
    )
);

$vin = (string)(
    $car['vin'] ?? ''
);


/*
 * Bejegyzés adatai
 */
$date = (string)(
    $entryData['date'] ?? ''
);

$km = (string)(
    $entryData['km'] ?? ''
);

$type = (string)(
    $entryData['type'] ?? 'simple'
);

$title = (string)(
    $entryData['title'] ?? ''
);

$description = (string)(
    $entryData['description'] ?? ''
);

$totalCost =
    $entryData['total_cost'] ?? 0;

$laborCost =
    $entryData['labor_cost'] ?? 0;


/*
 * Alkatrészek
 */
$items = (
    isset($entryData['items']) &&
    is_array($entryData['items'])
)
    ? $entryData['items']
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
                                <a href="/service/add.php?car=<?= (int)$carId ?>" class="leftmenu-back">Vissza</a>
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
                                                        <span class="epc-title"><?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?><?php endif; ?> - <?= htmlspecialchars($vin) ?> - Szervizbejegyzés ellenőrzése</span>
														</td>
													</tr>
													 <tr>
                                                        <td style="padding:20px;text-align:center;">
                                                            <span style="color:red;font-size:14px;">Figyelem!<br><br>Kérjük, ellenőrizze a bejegyzés minden adatát. A végleges mentés után a bejegyzés nem módosítható és nem törölhető.</span>
                                                        </td>
                                                    </tr>
													<!-- Üzenet -->
                                                    <?php if ($message !== ''): ?>
                                                    <tr>
                                                        <td style="padding:20px;text-align:center;">
                                                    	<span style="color:red;"><?= htmlspecialchars($message) ?></span>
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <tr>
														<td style=" padding:20px; text-align:center;">
														<!-- Alapadatok -->
														<table align="center" class="table-border" width="100%">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:400px;text-align:center;" class="p10">Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
															<!-- Dátum -->
															<tr>
																<td width="200" class="epc-text row-even p5 pl10">Dátum:
																</td>
																<td width="200" class="epc-text row-even p5 pr10 text-right"><?= htmlspecialchars($date) ?>
																</td>
															</tr>
															<!-- Kilométer -->
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Kilométer:
																</td>
																<td class="epc-text row-odd p5 pr10 text-right"><?php if ($km !== ''): ?><?= number_format( (int)$km, 0, ',', ' ') ?> km<?php else: ?>Nincs megadva<?php endif; ?>
																</td>
															</tr>
															<!-- Megnevezés -->
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Kategória:
																</td>
																<td class="epc-text row-even p5 pr10 text-right"><?= htmlspecialchars($title) ?>
																</td>
															</tr>
															<!-- Megjegyzés -->
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Megjegyzés:
																</td>
																<td class="epc-text row-odd p5 pr10 text-right"><?php if ($description !== ''): ?><?= nl2br( htmlspecialchars($description)) ?><?php endif; ?>
																</td>
															</tr>
														<!-- Egyszerű költség -->
														<?php if ($type === 'simple'): ?>
															<tr>
																<td width="200" class="pr5 epc-text row-odd p5 pl10" style="background-color:#444444;color:#ffffff;">Szerviz költsége:
																</td>
																<td width="200" class="epc-text row-odd p5 pr10 text-right" style="background-color:#444444;color:#ffffff;"><?= formatServicePrice($totalCost) ?>
																</td>
															</tr>
														<?php endif; ?>
														</table>
														<?php if ($type === 'detailed'): ?>
														<!-- Alkatrészek -->
														<table align="center" style="width:100%;text-align:center;" class="table-border servicestable2">											
															<tr>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;">Cikkszám
																</td>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
																</td>
																<td class="pr5 epc-text row-odd p5" style="background-color:#bb271a;color:#ffffff;">Darab
																</td>
																<td class="pr10 epc-text row-odd p5 text-right" style="background-color:#bb271a;color:#ffffff;">Egységár
																</td>
																<td class="pr10 epc-text row-odd p5 text-right" style="background-color:#bb271a;color:#ffffff;">Összesen
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
																<td class="pl10 epc-text <?= $rowClass ?> p5 text-left"><?= htmlspecialchars($partNumber) ?>
																</td>
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
														<table align="center" style="width:100%;text-align:center;" class="table-border servicestable2">	
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
														<!-- Véglegesítés -->
														<form method="post">
														<input type="hidden" name="action" value="confirm">
														<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
														<table align="center" class="table-border">
															<tr>
																<td style="width:250px;text-align:center;" class="p20"><button type="submit">Az adatok helyesek – véglegesítés</button>
																</td>
															</tr>
														</table>
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
</table>
</body>
</html>
