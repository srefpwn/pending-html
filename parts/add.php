<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

$partCategories = [
    'Folyadékok',
    'Szűrők',
    'Fék',
    'Futómű',
    'Motor',
    'Csavarok, alátétek',
  	'Patentek',
    'Egyéb'
];
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


$backUrl = '/cars/';

if (
    isset($_GET['back']) &&
    is_string($_GET['back']) &&
    $_GET['back'] !== ''
) {
    $backUrl = $_GET['back'];
}


$formError = '';
$partNumber = '';
$partName = '';
$partCategory = '';
$quantity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partNumber = trim((string)($_POST['part_number'] ?? ''));
    $partName = trim((string)($_POST['part_name'] ?? ''));
    $partCategory = trim((string)($_POST['part_category'] ?? ''));
    $quantityInput = trim((string)($_POST['quantity'] ?? ''));

    $quantity = filter_var($quantityInput, FILTER_VALIDATE_INT);

   if ($partNumber === '' || $partName === '') {
    $formError = 'A cikkszám és a megnevezés megadása kötelező.';
} elseif (!in_array($partCategory, $partCategories, true)) {
    $formError = 'Válassz egy érvényes kategóriát.';
} elseif ($quantity === false || $quantity < 1) {
    $formError = 'A darabszámnak legalább 1 egész számnak kell lennie.';
} else {
        $partsDir = $_SERVER['DOCUMENT_ROOT']
            . '/data/parts/' . $userId;

        $partsFile = $partsDir . '/' . $carId . '.json';

        if (
            !is_dir($partsDir)
            && !mkdir($partsDir, 0750, true)
            && !is_dir($partsDir)
        ) {
            $formError = 'Nem sikerült létrehozni az alkatrészek mappáját.';
        } else {
            $file = @fopen($partsFile, 'c+');

            if ($file === false) {
                $formError = 'Az alkatrészfájl nem nyitható meg írásra.';
            } elseif (!flock($file, LOCK_EX)) {
                fclose($file);
                $formError = 'Nem sikerült zárolni az alkatrészfájlt.';
            } else {
                $contents = stream_get_contents($file);
                $parts = [];

                if ($contents === false) {
                    $formError = 'Nem sikerült beolvasni az alkatrészfájlt.';
                } elseif (trim($contents) !== '') {
                    $decoded = json_decode($contents, true);

                    if (
                        json_last_error() !== JSON_ERROR_NONE
                        || !is_array($decoded)
                    ) {
                        $formError = 'Az alkatrészfájl tartalma hibás.';
                    } else {
                        $parts = $decoded;
                    }
                }

                if ($formError === '') {
                    $maxId = 0;

                    foreach ($parts as $part) {
                        if (isset($part['id']) && is_numeric($part['id'])) {
                            $maxId = max($maxId, (int)$part['id']);
                        }
                    }

                    $parts[] = [
                        'id' => $maxId + 1,
                        'part_number' => $partNumber,
                        'name' => $partName,
    					'category' => $partCategory,
                        'quantity' => $quantity
                    ];

                    $json = json_encode(
                        $parts,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                    );

                    if ($json === false) {
                        $formError = 'Nem sikerült előkészíteni a mentést.';
                    } else {
                        rewind($file);

                        if (
                            ftruncate($file, 0) === false
                            || fwrite($file, $json) === false
                            || !fflush($file)
                        ) {
                            $formError = 'Az alkatrész mentése nem sikerült.';
                        }
                    }
                }

                flock($file, LOCK_UN);
                fclose($file);

                if ($formError === '') {
                    $params = ['car' => $carId];

                    if (
                        isAdmin()
                        && $userId !== (int)($_SESSION['user_id'] ?? 0)
                    ) {
                        $params['user_id'] = $userId;
                    }

                    header('Location: /parts/?' . http_build_query($params));
                    exit;
                }
            }
        }
    }
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
                  			<?php if ($formError !== ''): ?>
                  			<tr>
                                <td style="padding:20px;text-align:center;">
                                <span style="font-size:14px;color:red;">
                                <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                </td>
                        	</tr>
                        	<?php endif; ?>
                  		</table>
                        <form id="part-add-form" method="post">
                        <table align="center" class="table-border" width="100%">
                            <tr>
                                <td style=" padding:20px; text-align:center;">
                                <table align="center" style="width:100%;text-align:center;" class="table-border">											
									<tr>
										<td class="pl10 epc-text row-odd p10 text-left" width="20%" style="background-color:#bb271a;color:#ffffff;">Cikkszám
										</td>
										<td class="pl10 epc-text row-odd p10 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
										</td>
										<td class="pl10 epc-text row-odd p10 text-left" style="background-color:#bb271a;color:#ffffff;">Kategória
										</td>
										<td class="epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Mennyiség
										</td>
										<td class="epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Mértékegység
										</td>
									</tr>
								</table>
                                <table align="center" style="width:100%;text-align:center;" class="table-border">
									<tr>
										<td class="pl10 epc-text row-even p5 text-left" width="20%">
										<input type="text" id="part_number" name="part_number" value="<?= htmlspecialchars($partNumber, ENT_QUOTES, 'UTF-8') ?>" required>
										</td>
										<td class="pl10 epc-text row-even p5 text-left">
										<input type="text" id="part_name" name="part_name" value="<?= htmlspecialchars($partName, ENT_QUOTES, 'UTF-8') ?>" required>
										</td>
										<td class="pl10 epc-text row-even p5 text-left">
										<select id="part_category" name="part_category" required>
											<option value="">Válassz kategóriát</option>
    										<?php foreach ($partCategories as $category): ?>
        									<option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"<?= $partCategory === $category ? 'selected' : '' ?>>
        									<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
        									</option>
    										<?php endforeach; ?>
										</select>
										</td>
										<td class="pl10 epc-text row-even p5 text-left">
										<select id="part_unit" name="unit" required>
    										<option value="db">Darab (db)</option>
    										<option value="l">Liter (l)</option>
    										<option value="kg">Kilogramm (kg)</option>
										</select>
										</td>
										<td class="epc-text row-even p5 text-center" width="7%">
										<input type="number" id="part_quantity" name="quantity"  min="1" step="1" value="<?= htmlspecialchars((string)$quantity, ENT_QUOTES, 'UTF-8') ?>" required>
										</td>
									</tr>
								</table>
                                </td>
                            </tr>
                        </table>
                        <table align="center" class="table-border" width="100%">
                       	 	<tr>
								<td style="padding:20px; text-align:center;">
								<table class="table-border text-center">
									<tr>
										<td>
										<button type="submit" id="part-add-submit">Mentés</button>
										</td>
									</tr>
								</table>
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
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
		</td>
	</tr>
</table>
</body>
</html>
