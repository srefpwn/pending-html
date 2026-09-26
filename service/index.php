<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

$backUrl = '/cars/';

if (
    isset($_GET['back']) &&
    is_string($_GET['back']) &&
    $_GET['back'] !== ''
) {
    $backUrl = $_GET['back'];
}
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
 * Szervizkönyv biztonságos kontextus
 *
 * Ellenőrzi:
 * - be van-e jelentkezve a felhasználó
 * - létezik-e az autó
 * - az autó a felhasználóhoz tartozik
 */
$service = getServiceContext($carId);

$userId = $service['user_id'];
$car = $service['car'];


/*
 * Szervizadatok betöltése
 */
$serviceResult = loadServiceData(
    $userId,
    $carId
);

if (!$serviceResult['success']) {
    http_response_code(500);

    $serviceError = $serviceResult['error'];

    require __DIR__ . '/service_error.php';
    exit;
}

$serviceData = $serviceResult['data'];

$entries = $serviceData['entries'];


/*
 * Szervizbejegyzések rendezése
 *
 * A legfrissebb dátumú bejegyzés kerül felülre.
 * Az eredeti JSON sorrendje nem változik.
 */
usort(
    $entries,
    function ($a, $b) {

        $dateA = (string)($a['date'] ?? '');
        $dateB = (string)($b['date'] ?? '');

        return strcmp($dateB, $dateA);
    }
);

/*
 * Szervizbejegyzések csoportosítása év szerint
 *
 * Az entries már dátum szerint csökkenő sorrendben van,
 * ezért az évek is automatikusan csökkenő sorrendben
 * kerülnek létrehozásra.
 */
$entriesByYear = [];

foreach ($entries as $entry) {

    $date = trim(
        (string)($entry['date'] ?? '')
    );

    if ($date === '') {
        continue;
    }

    $year = substr($date, 0, 4);

    if (!isset($entriesByYear[$year])) {
        $entriesByYear[$year] = [];
    }

    $entriesByYear[$year][] = $entry;
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

$carConfig = getCarConfig($car);

$serviceTipsEnabled =
    is_array($carConfig) &&
    (int)($carConfig['servicetips_enable'] ?? 0) === 1;
?>

<html>
<head>
	<title>RichCars - Szervizkönyv</title>	
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
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title"><?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?><?php else: ?>Szervizkönyv<?php endif; ?> - <?= htmlspecialchars($vin) ?> - Szervizkönyv</span>
                       			</td>
                  			</tr>
                  		</table>
                  		<table style="width:100%;" class="table-border">
							<tr>
								<td style="padding:20px; text-align:center;">
								<table class="table-border text-center">
									<tr>
										<td>
										<a href="/service/add.php?car=<?= (int)$carId ?>"><button type="submit">Új bejegyzés</button></a>
										</td>
										<?php if ($serviceTipsEnabled): ?>
										<td>
										<a href="/service/servicetips.php?car=<?= (int)$carId ?>"><button type="submit">Szerviz információk</button></a>
										</td>
										<?php endif; ?>
									</tr>
								</table>
								</td>
							</tr>
							<tr>
								<td class="textv-top">
								<table class="servicestable" width="100%" style="table-layout:fixed;padding-bottom:20px;">
								<?php if (empty($entries)): ?>
									<tr>
										<td style=" padding:40px; text-align:center;">
										<span class="epc-title2">Még nincs rögzített szervizbejegyzés.</span>
										</td>
									</tr>
								 <?php else: ?>
<?php $entryIndex = 0; ?>
									<tr>
<?php foreach ($entriesByYear as $year => $yearEntries): ?>
    <?php $yearEntryIndex = 0; ?>
    <?php foreach ($yearEntries as $entry): ?>
        <?php
        $entryId = isset($entry['id'])
            && is_numeric($entry['id'])
            ? (int)$entry['id']
            : 0;
        if ($entryId <= 0) {
            continue;
        }
        if (
            $entryIndex > 0 &&
            $entryIndex % 4 === 0
        ):
        ?>
                    				</tr>
                    				<tr>
        <?php endif; ?>
        <?php $entryIndex++; $yearEntryIndex++; ?>
    									<td width="25%" style="padding:10px; vertical-align:top;">
        								<table class="table-border" width="100%">
           				 					<tr>
                								<td class="textv-center datebox2-top">
												<span class="databox-text5"><?= number_format((int)$entry['km'], 0, ',', '.') ?> km / 12 hónap</span>
                								</td>
            								</tr>
            								<tr>
                								<td style="padding:20px;padding-top:10px;padding-bottom:0px; background-color:#cccccc;height:180px;" class="textv-top">
                								<table class="table-border">
                									<tr>
                										<td class="pr10 textv-top">
                										<table class="table-border text-right">
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><b>Dátum:</b></span></td>
                											</tr>
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><b>Km:</b></span></td>
                											</tr>
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><b>Kategória:</b></span></td>
                											</tr>
                											<tr>
                												<td class="pt5"><span class="databox-text2"><b>Megjegyzés:</b></span></td>
                											</tr>
                										</table>
                										</td>
                										<td class="textv-top">
                										<table class="table-border">
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><?= htmlspecialchars( date('Y.m.d.', strtotime($entry['date'] ?? ''))) ?></span>
                    											<?php if (!empty($entry['km'])): ?>
                												</td>
                											</tr>
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><?= number_format((int)$entry['km'], 0, ',', '.') ?> km</span>
                   												 <?php endif; ?>
                												</td>
                											</tr>
                											<tr>
                												<td style="height:30px;"><span class="databox-text2"><?= htmlspecialchars($entry['title'] ?? '') ?></span>
                												</td>
                											</tr>
                											<tr>
                												<td class="pt5"><span class="databox-text2"><?= htmlspecialchars(mb_strlen($entry['description'] ?? '') > 55 ? mb_substr($entry['description'], 0, 55) . '...' : ($entry['description'] ?? 'Szervizbejegyzés')) ?></span>
                												</td>
                											</tr>
                										</table>
                										</td>
                									</tr>
                								</table>
                								</td>
            								</tr>
           									<tr>
                								<td class="text-center textv-bottom" style="padding:20px;background-color:#cccccc;">
                    							<a href="/service/view.php?entry=<?= (int)$entryId ?><?= $navigationParams ?>"><button type="submit">Részletek</button></a>
                								</td>
            								</tr>
       									</table>
    									</td>

    <?php endforeach; ?>

<?php endforeach; ?>

<?php if ($entryIndex % 4 !== 0): ?>

</tr>

<?php endif; ?>

        <?php endif; ?>
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
