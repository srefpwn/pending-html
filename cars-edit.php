<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars-functions.php';

/*
 * Admin jogosultság
 *
 * Ezt egyelőre nem ellenőrizzük itt,
 * amíg nem azonosítottuk a projektben használt
 * tényleges admin jogosultság-ellenőrzést.
 */

/*
 * Azonosítók
 */

$userId = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);

$carId = filter_input(
    INPUT_GET,
    'car_id',
    FILTER_VALIDATE_INT
);

if (
    $userId === false ||
    $userId === null ||
    $userId < 1 ||
    $carId === false ||
    $carId === null ||
    $carId < 1
) {
    http_response_code(400);
    exit('Érvénytelen azonosító.');
}

/*
 * Összes felhasználói autó betöltése
 */

$carsData = loadUserCarsData();

$userKey = (string)$userId;

if (
    !isset($carsData[$userKey]) ||
    !is_array($carsData[$userKey])
) {
    http_response_code(404);
    exit('A felhasználó autói nem találhatók.');
}

/*
 * A konkrét autó megkeresése
 */

$car = null;

foreach ($carsData[$userKey] as $item) {

    if (
        isset($item['id']) &&
        (int)$item['id'] === $carId
    ) {
        $car = $item;
        break;
    }
}

if ($car === null) {
    http_response_code(404);
    exit('A kért autó nem található.');
}

/*
 * VIN konfiguráció
 */

$carConfig = getCarConfig($car);

?>

<!DOCTYPE html>
<html>
	<head>
	<title>RichCars - Autó szerkesztése</title>
	<meta charset="UTF-8">
	<link rel="icon" href="/favicon.ico" type="image/x-icon"/>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
	<link href="/css.css" rel="stylesheet" type="text/css"/>
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
						<a href="/cars/" class="leftmenu-back">Vissza</a>
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
										<span class="epc-title">Autó szerkesztése</span>
										</td>
									</tr>
									<tr>
										<td style="padding:20px;text-align:center;">
										<strong><?= htmlspecialchars($car['name'] ?? '') ?></strong><br><br>VIN: <?= htmlspecialchars($car['vin'] ?? '') ?><br>User ID:<?= (int)$userId ?><br>Car ID:<?= (int)$carId ?>
										</td>
									</tr>
									<tr>
										<td style="padding:20px;">
										<table align="center" class="table-border" width="100%">
											<tr>
												<td class="epc-text row-even p5 text-left">Autó neve:</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['name'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">VIN:</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['vin'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Márka:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['brand'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Modell:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['model'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Évjárat:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['production_year'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Series:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['series'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Karosszéria:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['body'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Motor:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['engine'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Felszereltség:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['trim'] ?? '') ?>
												</td>
											</tr>
											<tr>
												<td class="epc-text row-even p5 text-left">Szín:
												</td>
												<td class="epc-text row-odd p5 text-left"><?= htmlspecialchars($car['color'] ?? '') ?>
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
</body>
</html>
