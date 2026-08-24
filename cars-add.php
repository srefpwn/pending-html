<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once __DIR__ . '/functions.php';


// Vissza URL
$backUrl = '/cars/';


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
         * Adatok
         */
        $vin = $_POST['vin'] ?? '';
        $name = $_POST['name'] ?? '';

        $productionYear = $_POST['production_year'] ?? '';
        $productionMonth = $_POST['production_month'] ?? '';
        $body = $_POST['body'] ?? '';
        $engine = $_POST['engine'] ?? '';
        $trim = $_POST['trim'] ?? '';
        $color = $_POST['color'] ?? '';
        $colorCode = $_POST['color_code'] ?? '';


        /*
         * Autó hozzáadása
         */
        $result = addUserCar(
            $vin,
            $name,
            $productionYear,
            $productionMonth,
            $body,
            $engine,
            $trim,
            $color,
            $colorCode
        );


        /*
         * Üzenet mentése sessionbe
         */
$_SESSION['cars_message'] = $result['message'];
$_SESSION['cars_message_type'] =
    $result['success'] ? 'success' : 'error';

if ($result['success']) {
    header('Location: /cars/');
} else {
    header('Location: /cars/add.php');
}

exit;
    }
}


// Sessionből érkező üzenet
$message = $_SESSION['cars_message'] ?? '';
$messageType = $_SESSION['cars_message_type'] ?? '';


// Üzenet egyszeri felhasználása
unset(
    $_SESSION['cars_message'],
    $_SESSION['cars_message_type']
);

?>
<html>
<head>
    <title>RichCars - Autó hozzáadása</title>
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
                                    <table align="left" width="100%" class="table-border">
                                        <tr>
                                            <td class="textv-top">
                                                <table align="left" class="table-border" width="100%">
                                                    <!-- Cím -->
                                                    <tr>
                                                        <td style="padding:0px;text-align:center;">
                                                            <span class="epc-title">Autó hozzáadása</span>
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
                                                        <td style="padding:20px;text-align:center;">
                                                    	<form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
														<table align="center" class="table-border" width="100%">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:400px;text-align:center;" class="p10">Adatok
																</td>
															</tr>
														</table>
                                                        <table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
                                                        <!-- Autó neve -->
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Autó neve:
																</td>
																<td width="50%" class="row-even p5">
																<input type="text" name="name" maxlength="50">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Alvázszám:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="text" name="vin" maxlength="30" placeholder="Alvázszám" required>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Gyártási idő:
																</td>
																<td width="50%" class="row-even p5">
																<span class="select-wrapper2">
																<select name="production_year" required>
                                                                <?php for ( $year = 2003; $year <= 2008; $year++ ): ?>
                                                                	<option value="<?= $year ?>"><?= $year ?></option>
                                                                <?php endfor; ?>
                                                                </select></span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-odd p5">Kivitel:
																</td>
																<td width="50%" class="row-odd p5"><span class="select-wrapper2">
																<select name="body" required><?php foreach ( $car_options['body'] as $key => $name ): ?>
                                                                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                                                                <?php endforeach; ?>
                                                                </select></span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-even p5">Motor:
																</td>
																<td width="50%" class="row-even p5"><span class="select-wrapper2">
																<select name="engine" required>
                                                                                <?php
                                                                                foreach (
                                                                                    $car_options['engine']
                                                                                    as $key => $name
                                                                                ):
                                                                                ?>
                                                                            	<option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select></span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-odd p5">Felszereltség:
																</td>
																<td width="50%" class="row-odd p5"><span class="select-wrapper2">
																<select  name="trim" required>
                                                                                <?php
                                                                                foreach (
                                                                                    $car_options['trim']
                                                                                    as $key => $name
                                                                                ):
                                                                                ?>
                                                                            	<option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select></span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-even p5">Szín:
																</td>
																<td width="50%" class="row-even p5"><span class="select-wrapper2">
																<select name="color" required>
                                                                                <?php
                                                                                foreach (
                                                                                    $car_options['color']
                                                                                    as $code => $name
                                                                                ):
                                                                                ?>
                                                                                    <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code) ?> - <?= htmlspecialchars($name) ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select></span>
																</td>
															</tr>
                                                        </table>
                                                        <table class="table-border text-center">
                                                        <!-- Gombok -->
                                                            <tr>
                                                                <td></td>
                                                                <td style="padding-top:10px;">
                                                                <button type="submit">Autó hozzáadása
                                                                </button>
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
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
    </tr>
</table>
</body>
</html>
