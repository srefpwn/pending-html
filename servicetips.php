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
 * Saját oldalra való visszatérési cím
 */
if (empty($_GET['back'])) {

    $_GET['back'] =
        '/service/servicetips.php?car=' .
        urlencode($carId);
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


/*
 * Autó adatai
 */
$carName = trim(
    (string)($car['name'] ?? '')
);

$vin = (string)(
    $car['vin'] ?? ''
);



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
                                                        <?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?> - <?php endif; ?><?= htmlspecialchars($vin) ?> - Szerviz információk - Szervizkönyv
														</span>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Motorolaj Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">maximum: 20.000 km vagy 12 hónap
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Típusa:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">0W30 A5/B5
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Mennyiség (összes):
																</td>
																<td class="epc-text row-even p5 text-right pr10">6,5 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Mennyiség (csere):
																</td>
																<td class="epc-text row-odd p5 text-right pr10">5,5 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Mennyiség (szűrő):
																</td>
																<td class="epc-text row-even p5 text-right pr10">0,2 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Szűrő cikkszáma:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">15430-RBD-E02
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Leeresztő csavar:
																</td>
																<td class="epc-text row-even p5 text-right pr10">39 NM
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Alátét mérete:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">14 mm
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-even p5 pl10">Alátét cikkszáma:
																</td>
																<td class="epc-text row-even p5 text-right pr10">94109-14000
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Üzemanyagszűrő Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">maximum: 40.000 km
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Szűrő cikkszáma:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">16901-RMA-E00
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Levegőszűrő Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">40.000 km
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Szűrő cikkszáma:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">17220-RBD-E00
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Váltóolaj Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">120.000 km vagy 96 hónap
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Típus:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">Honda MTF-3
																</td>
															</tr><tr>
																<td width="400" class="epc-text row-even p5 pl10">összesen:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">2,5 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Csere:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">2,2 liter
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Hűtőfolyadék Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">először: 100.000 km majd 60.000 km
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Típus:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">Honda Type 2 ALL SEASON COOLANT
																</td>
															</tr>
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">összesen:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">8 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Csere:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">6,8 liter
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Fékfolyadék Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">36 havonta
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Típus:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">Honda BF DOT4
																</td>
															</tr>
														</table>
														</td>
													</tr>
													<tr>
														<td style=" padding:20px;padding-top:0px; text-align:center;">
														<table align="center" class="table-border" style="width:100%;">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;" class="p10">Szervófolyadék Adatok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px; width:100%;" class="table-border">
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Csere intervallum:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">először 100.000 km majd 50.000 km
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Típus:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">Honda PSF-S
																</td>
															</tr>
															<tr>
																<td width="400" class="epc-text row-even p5 pl10">Összesen:
																</td>
																<td width="400" class="epc-text row-even p5 text-right pr10">1.1 liter
																</td>
															</tr>
															<tr>
																<td class="pr5 epc-text row-odd p5 pl10">Csere:
																</td>
																<td class="epc-text row-odd p5 text-right pr10">0,9 liter
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
				</td>
			</tr>
		</table>
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
		</td>
	</tr>
</table>
</body>
</html>
