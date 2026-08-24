<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once __DIR__ . '/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

// Vissza URL
$backUrl = '/';


// Üzenetek
$message = '';
$messageType = '';


// CSRF token
if (empty($_SESSION['cars_csrf_token'])) {
    $_SESSION['cars_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['cars_csrf_token'];

// POST kezelés
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF ellenőrzés
     */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['cars_csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        $_SESSION['cars_message'] = 'Érvénytelen kérés.';
        $_SESSION['cars_message_type'] = 'error';

    } else {

        /*
         * Autó törlése
         */
        if (
            isset($_POST['action']) &&
            $_POST['action'] === 'delete'
        ) {

            $carId = filter_input(
                INPUT_POST,
                'car_id',
                FILTER_VALIDATE_INT
            );

            if ($carId === false || $carId === null) {

                $_SESSION['cars_message'] = 'Érvénytelen autóazonosító.';
                $_SESSION['cars_message_type'] = 'error';

            } elseif (deleteUserCar($carId)) {

                $_SESSION['cars_message'] = 'Az autó sikeresen törölve.';
                $_SESSION['cars_message_type'] = 'success';

            } else {

                $_SESSION['cars_message'] = 'Az autó nem található, vagy a törlés sikertelen.';
                $_SESSION['cars_message_type'] = 'error';
            }
        }
    }

    /*
     * POST után mindig GET-re váltunk
     */
    header('Location: /cars/');
    exit;
}
/*
 * Sessionből érkező üzenet kiolvasása
 */
$message = $_SESSION['cars_message'] ?? '';
$messageType = $_SESSION['cars_message_type'] ?? '';

/*
 * Üzenet egyszeri felhasználása
 */
unset(
    $_SESSION['cars_message'],
    $_SESSION['cars_message_type']
);

// Saját autók betöltése
$userCars = getUserCars();

?>
<html>
<title>RichCars - Autóim</title>
<head>
<meta charset="UTF-8">
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
                                <a href="<?= htmlspecialchars($backUrl) ?>" class="leftmenu-back">Vissza</a><br>
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
                                <!--
                                 * Autóim fő tartalom
                                 -->
                                <table align="left" width="100%" class="table-border">
                                    <tr>
                                        <td class="textv-top">
                                            <table align="left" class="table-border" width="100%">
                                                <!--
                                                 * Cím
                                                 -->
                                                <tr>
                                                    <td style="padding:0px;text-align:center;">
                                                        <span class="epc-title">Autóim</span>
                                                    </td>
                                                </tr>
                                                <tr>
													<td style="padding:20px;text-align:center;">
													<table class="table-border text-center">
														<tr>
															<td>
															<a href="/cars/add.php"><button type="submit">Autó hozzáadása</button></a>
															</td>
														</tr>
													</table>
													</td>
												</tr>
                                                <!--
                                                 * Üzenet
                                                 -->
                                                <?php if ($message !== ''): ?>
                                                <tr>
                                                    <td style="padding:20px;text-align:center;">
                                                        <?php if ($messageType === 'success'): ?>
                                                            <span style="font-size:14px;color:green;">
                                                                <?= htmlspecialchars($message) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="font-size:14px;color:red;">
                                                                <?= htmlspecialchars($message) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                <!--
                                                 * Saját autók
                                                 -->
                                                <tr>
                                                    <td class="textv-top">
                                                        <table class="menutable" width="100%">
<?php if (empty($userCars)): ?>
                                                            <tr>
                                                                <td style="padding:20px;text-align:center;">
                                                                <table width="100%">
                                                                	<tr>
                                                                		<td style="width:100%;text-align:center;">
                                                                		<span class="epc-title">Jelenleg nincs hozzáadott autód.</span>
                                                                		</td>
                                                                	</tr>
                                                                </table>
                                                                </td>
                                                            </tr>
<?php else: ?>
<?php foreach ($userCars as $car): ?>

<?php

$carConfig = getCarConfig($car);

if ($carConfig === null) {
    continue;
}

$carName = trim((string)($car['name'] ?? ''));
$vin = (string)($car['vin'] ?? '');
$brand = (string)($car['brand'] ?? '');
$model = (string)($car['model'] ?? '');

$carOptions = $car_catalog[$brand]['models'][$model]['options'] ?? [];
?>
                                                            <tr>
                                                                <td style="padding:0px;background-color:#cccccc;">
                                                                    <table width="100%" class="text-center table-border">
                                                                    	<tr>
                                                                    		<td style="height:auto;padding:10px;background-color:#444444;"><span class="epc-title3"><?php if ($carName !== ''): ?><?= htmlspecialchars($car['name']) ?> - <?php endif; ?><?= htmlspecialchars($carConfig['name']) ?></span>
                                                                    		</td>
                                                                    	</tr>
                                                                    </table>
                                                                    <table width="100%" class="text-center table-border">
                                                                        <tr>
                                                                            <td style="width:352px;text-align:center; padding-top:20px;padding-bottom:20px;">
                                                                            <?php if (!empty($carConfig['preview'])): ?>
                                                                            <img src="<?= htmlspecialchars($carConfig['preview']) ?>" width="312">
                                                                            <?php endif; ?>
                                                                            </td>
                                                                            <td class="textv-center" style="padding-left:0px; padding-top:20px;padding-bottom:20px;"">
                                                                            <table class="table-border" width="100%">
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;width:15%;">
                                                                            		<span class="epc-text2"><b>Alvázszám: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($car['vin']) ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;" class="car-list">
                                                                            		<span class="epc-text2"><b>Gyártási év: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;" class="car-list">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($car['production_year']) ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;">
                                                                            		<span class="epc-text2"><b>Kivitel: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($carOptions['body'][$car['body']] ?? '') ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;" class="car-list">
                                                                            		<span class="epc-text2"><b>Motor: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;" class="car-list">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($carOptions['engine'][$car['engine']] ?? '') ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;">
                                                                            		<span class="epc-text2"><b>Felszereltség: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($carOptions['trim'][$car['trim']] ?? '') ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;" class="car-list">
                                                                            		<span class="epc-text2"><b>Szín: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:right;" class="car-list">
                                                                            		<span class="epc-text2"><?= htmlspecialchars($carOptions['color'][$car['color']] ?? '') ?> - <?= htmlspecialchars($car['color'] ?? '') ?></span>
                                                                            		</td>
                                                                            	</tr>
                                                                            </table>
                                                                            </td>
                                                                            <td style="width:140px; padding:20px;">
                                                                            <table class="table-border text-right">
                                                                            	<tr>
                                                                            		<td style="height:30px;"><a href="/service/?car=<?= (int)$car['id'] ?><?= $navigationParams ?>"><button style="width:140px" type="submit">Szervizkönyv</button></a>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:30px;"><a href="/7gen/epc/?page=<?= urlencode($carConfig['epc_page']) ?>&car=<?= (int)$car['id'] ?>&from_cars=<?= urlencode('/cars/') ?><?= $navigationParams ?>"><button style="width:140px" type="submit">EPC megnyitása</button></a>
                                                                            		</td>
                                                                            	</tr>
                                                                            	<tr>
                                                                            		<td style="height:30px;"><a href="/manual/?type=<?= urlencode($carConfig['manual_type']) ?>&year=<?= htmlspecialchars($car['production_year']) ?>&car=<?= (int)$car['id'] ?>&from_cars=<?= urlencode('/cars/') ?><?= $navigationParams ?>"><button style="width:140px" type="submit">Szerviz Kézikönyv</button>
</a>
                                                                            		</td>
                                                                            	</tr>
                                                                            </table>
                                                                            <table class="table-border text-right">
                                                                            	<tr>
                                                                            		<td style="height:82px;" class="textv-bottom">
                                                                            		<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																					<input type="hidden" name="action" value="delete">
																					<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																					<button style="width:140px" type="submit">Törlés</button>
																					</form>
                                                                            		</td>
                                                                            	</tr>
                                                                            </table>
                                                                            </td>
                                                                        </tr>
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
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
        </td>
    </tr>
</table>
</body>
</html>
