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

/*
 * Service Tips konfiguráció
 *
 * A funkció engedélyezése és a modellhez tartozó
 * Service Tips JSON a VIN konfigurációból érkezik.
 */

$vinConfig = $vin_configs[$vin] ?? null;

$serviceTipsEnabled =
    (int)($vinConfig['servicetips_enable'] ?? 0) === 1;

$serviceTipsData = null;

if ($serviceTipsEnabled) {

    $series = trim(
        (string)($vinConfig['series'] ?? '')
    );

    $modelCode = trim(
        (string)($vinConfig['model_code'] ?? '')
    );

    if ($series !== '' && $modelCode !== '') {

        $jsonFile =
            $_SERVER['DOCUMENT_ROOT']
            . '/data/service/servicetips/honda/accord/'
            . $series
            . '/'
            . strtolower($modelCode)
            . '.json';

        if (is_file($jsonFile)) {

            $json = file_get_contents($jsonFile);

            if ($json !== false) {

                $decoded = json_decode(
                    $json,
                    true
                );

                if (
                    is_array($decoded) &&
                    isset($decoded['topics']) &&
                    is_array($decoded['topics'])
                ) {
                    $serviceTipsData = $decoded;
                }
            }
        }
    }
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
                                                        <?php if ($carName !== ''): ?><?= htmlspecialchars($carName) ?> - <?php endif; ?><?= htmlspecialchars($vin) ?> - Szerviz információk - Szervizkönyv
														</span>
														</td>
													</tr>
<?php if ($serviceTipsData !== null): ?>

    <?php foreach ($serviceTipsData['topics'] as $topic): ?>

        <?php
        $topicTitle = trim(
            (string)($topic['title'] ?? '')
        );

        $items = $topic['items'] ?? [];

        if (
            $topicTitle === '' ||
            !is_array($items)
        ) {
            continue;
        }
        ?>

        <tr>
            <td style="padding:20px;padding-bottom:0px;text-align:center;">

                <table
                    align="center"
                    class="table-border"
                    style="width:100%;"
                >
                    <tr>
                        <td
                            style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;"
                            class="p10"
                        >
                            <?= htmlspecialchars($topicTitle) ?>
                        </td>
                    </tr>
                </table>

                <table
                    align="center"
                    style="text-align:left;border-spacing:0px;padding-bottom:20px;width:100%;"
                    class="table-border"
                >

                    <?php foreach ($items as $index => $item): ?>

                        <?php
                        $label = trim(
                            (string)($item['label'] ?? '')
                        );

                        $value = trim(
                            (string)($item['value'] ?? '')
                        );

                        if ($label === '') {
                            continue;
                        }

                        $rowClass =
                            ($index % 2 === 0)
                            ? 'row-even'
                            : 'row-odd';
                        ?>

                        <tr>

                            <td
                                class="pr5 epc-text <?= $rowClass ?> p5 pl10"
                            >
                                <?= htmlspecialchars($label) ?>:
                            </td>

                            <td
                                class="epc-text <?= $rowClass ?> p5 text-right pr10"
                            >
                                <?= htmlspecialchars($value) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>

            </td>
        </tr>

    <?php endforeach; ?>

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
