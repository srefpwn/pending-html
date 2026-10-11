<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/parts/functions.php';

/*
 * Autó és felhasználó azonosítása
 */
$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

$userId = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);

/*
 * Az autó ID kötelező és érvényes legyen.
 */
if (
    $carId === false ||
    $carId === null ||
    $carId <= 0
) {
    denyAccess();
}

/*
 * Másik felhasználó autóját csak admin nyithatja meg.
 */
if ($userId !== false && $userId !== null) {
    if (!isAdmin()) {
        denyAccess();
    }

    $targetUser = getUserById($userId);

    if ($targetUser === null) {
        denyAccess();
    }
} else {
    $userId = (int)($_SESSION['user_id'] ?? 0);
}

/*
 * Megkeressük az autót a tulajdonos autói között.
 */
$carsData = loadUserCarsData();
$userCars = $carsData[(string)$userId] ?? [];

$car = null;

foreach ($userCars as $userCar) {
    if (
        isset($userCar['id']) &&
        (int)$userCar['id'] === $carId
    ) {
        $car = $userCar;
        break;
    }
}

if ($car === null) {
    denyAccess();
}

/*
 * Az autó adatai a meglévő adatforrásból.
 */
$carName = trim((string)($car['name'] ?? ''));
$vin = (string)($car['vin'] ?? '');

$partsLoadError = false;
$parts = [];

try {
    $parts = loadCarParts((int)$userId, (int)$carId);
} catch (RuntimeException $e) {
    $partsLoadError = true;
}

$backUrl = '/cars/';

if (
    isset($_GET['back']) &&
    is_string($_GET['back']) &&
    $_GET['back'] !== ''
) {
    $backUrl = $_GET['back'];
}



?>

<html>
<head>
	<title>RichCars - Alkatrészek</title>	
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
				<table style="width:100%;min-height:300px;">
					<tr>
						<td class="textv-top">
                        <table align="left" width="100%" class="table-border">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title"><?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?><?php else: ?>Alkatrészek<?php endif; ?> - <?= htmlspecialchars($vin) ?> - Alkatrészek</span>
                       			</td>
                  			</tr>
                  		</table>
                  		<table style="width:100%;" class="table-border">
							<tr>
								<td style="padding:20px; text-align:center;">
								<table class="table-border text-center">
									<tr>
										<td>
<a href=""><button type="submit">Alkatrész hozzáadása</button></a>
										</td>
									</tr>
								</table>
								</td>
							</tr>
                            <!--
                             * Üzenet
                            -->
                            <?php if ($partsLoadError): ?>
                            <tr>
                                <td style="padding:20px;text-align:center;">
                                <span style="font-size:14px;color:red;">
                                Az alkatrészlista betöltése sikertelen. Kérjük, próbáld meg később.
                                </span>
                                </td>
                        	</tr>
                           	<?php elseif (empty($parts)): ?>
                            <tr>
                                <td style="padding:20px;text-align:center;">
                                <span style="font-size:14px;color:red;">
                                Még nincsenek alkatrészek hozzáadva.
                                </span>
                                </td>
                            </tr>
                            <?php else: ?>
                        </table>
                        <table align="center" class="table-border" width="100%">
                            <tr>
                                <td style=" padding:20px; text-align:center;">
                                <table align="center" style="width:100%;text-align:center;" class="table-border">											
									<tr>
										<td class="pl10 epc-text row-odd p10 text-left" width="20%" style="background-color:#bb271a;color:#ffffff;">Cikkszám
										</td>
										<td class="pl10 epc-text row-odd p10 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
										</td>
										<td class="epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Darab
										</td>
										<td class="pl10 epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Változás
										</td>
										<td class="row-odd" width="140" style="background-color:#bb271a;color:#ffffff;">
										</td>
										<td class="row-odd" width="140" style="background-color:#bb271a;color:#ffffff;">
										</td>
									</tr>
								</table>
                                <table align="center" style="width:100%;text-align:center;" class="table-border">
                        <?php foreach ($parts as $index => $part): ?>
                        <?php
                        $rowClass = ($index % 2 === 0)
                            ? 'row-even'
                            : 'row-odd';
                        ?>
									<tr>
										<td class="pl10 epc-text <?= $rowClass ?> p5 text-left" width="20%"><?= htmlspecialchars( $part['part_number'],  ENT_QUOTES, 'UTF-8' ) ?>
										</td>
										<td class="pl10 epc-text <?= $rowClass ?> p5 text-left"><?= htmlspecialchars( $part['name'],  ENT_QUOTES,  'UTF-8' ) ?>
										</td>
										<td class="epc-text <?= $rowClass ?> p5 text-center" width="7%"><?= $part['quantity'] ?>
										</td>
										<td class="epc-text <?= $rowClass ?> p5 text-center" width="7%"><input type="text">
										</td>
										<td class="<?= $rowClass ?>" width="140"><a href=""><button style="width:140px" type="submit">Mentés</button></a>
										</td>
										<td class="<?= $rowClass ?>" width="140"><button style="width:140px" type="submit">Törlés</button>
										</td>
									</tr>
                    	<?php endforeach; ?>
								</table>
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
