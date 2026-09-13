<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

$brand = strtolower(trim($_GET['brand'] ?? ''));
$model = strtolower(trim($_GET['model'] ?? ''));
$series = strtolower(trim($_GET['series'] ?? ''));
$modelCode = strtolower(trim($_GET['model_code'] ?? ''));
$bodyCode = strtolower(trim($_GET['body_code'] ?? ''));
$trimCode = strtolower(trim($_GET['trim_code'] ?? ''));


$partNumber = trim($_GET['part_number'] ?? '');


$back = $_GET['back'] ?? '';
$from_cars = $_GET['from_cars'] ?? '';
$carId = filter_input( INPUT_GET, 'car', FILTER_VALIDATE_INT);

$userCar = null;
$userCarConfig = null;

if ($carId !== false && $carId !== null) {

    $userCars = getUserCars();

    foreach ($userCars as $car) {

        if (
            isset($car['id']) &&
            (int)$car['id'] === $carId
        ) {
            $userCar = $car;
            break;
        }
    }

if ($userCar !== null) {

    $vin = $userCar['vin'] ?? '';

    $userCarConfig = $vin_configs[$vin] ?? null;

    if (
        $userCarConfig === null ||
        ($userCar['brand'] ?? '') !== $brand ||
        ($userCar['model'] ?? '') !== $model ||
        ($userCar['series'] ?? '') !== $series ||
        ($userCarConfig['model_code'] ?? '') !== $modelCode ||
        ($userCarConfig['body_code'] ?? '') !== $bodyCode ||
        ($userCarConfig['trim_code'] ?? '') !== $trimCode
    ) {
        $userCar = null;
        $userCarConfig = null;
    }
}
}

?>
<html>
<head>
	<title>RichCars - EPC Kereső</title>
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
				<td class="submenu textv-top" style="width:250px; min-width:250px; max-width:250px;">
				<table width="250" style="width:250px; min-width:250px; max-width:250px;" align="left">
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
				<table align="left" width="100%">
					<tr>
						<td>
<?php
/**
 * Keresési adatok
 */


/**
 * Csak engedélyezett típus használható.
 */
$config = null;

foreach ($configs as $item) {

    if (
        ($item['brand'] ?? '') === $brand &&
        ($item['model'] ?? '') === $model &&
        ($item['series'] ?? '') === $series &&
        ($item['model_code'] ?? '') === $modelCode &&
        ($item['body_code'] ?? '') === $bodyCode &&
        ($item['trim_code'] ?? '') === $trimCode
    ) {
        $config = $item;
        break;
    }
}

$epcError = '';

if (
    (
        $brand !== '' ||
        $model !== '' ||
        $series !== '' ||
        $modelCode !== '' ||
        $bodyCode !== '' ||
        $trimCode !== ''
    ) &&
    $config === null
) {
    $epcError =
        'A kiválasztott autóhoz EPC nem érhető el.';
}
/**
 * Cikkszám normalizálása.
 *
 * Például:
 *
 * 34251-S5A-003
 * 34251 S5A 003
 *
 * keresés szempontjából ugyanaz lesz.
 */
function normalizePartNumber(string $value): string
{
    return strtoupper(
        preg_replace('/\s+/', '', trim($value))
    );
}

function normalizeName(string $value): string
{
    $value = strtoupper(trim($value));
    return preg_replace('/\s+/', ' ', $value);
}

function nameContainsAllWords(string $name, string $search): bool
{
    $name = normalizeName($name);
    $search = normalizeName($search);

    $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($words as $word) {
        if (strpos($name, $word) === false) {
            return false;
        }
    }

    return true;
}
/**
 * EPC téma képének nevének meghatározása.
 */
function getImageName(string $id, string $extension): string
{
    if (substr_count($id, '_') === 1) {
        return $id . '_.' . $extension;
    }

    return $id . '.' . $extension;
}

/**
 * Keresési kifejezés normalizálása.
 */
$searchPartNumber = normalizePartNumber($partNumber);
$searchName = normalizeName($partNumber);

/**
 * EPC JSON betöltése.
 */
$epc = null;

if ($epcError === '') {

    $epcFile = $config['epc_json'];

    if (!is_file($epcFile)) {
        http_response_code(500);
        exit('Az EPC adatfájl nem található.');
    }

    $json = file_get_contents($epcFile);

    $epc = json_decode($json, true);
}
/**
 * Ellenőrizzük a JSON-t.
 */
if ($epcError === '' && !is_array($epc)) {
    http_response_code(500);
    exit('Az EPC adatfájl nem olvasható.');
}

/**
 * Találatok.
 */
$results = [];


/**
 * Ha van keresési kifejezés, elvégezzük a keresést.
 */
if ($epcError === '' && $partNumber !== '') {

    foreach ($epc as $page) {

        /**
         * Csak olyan EPC-oldallal foglalkozunk,
         * amelynek vannak parts adatai.
         */
        if (
            !isset($page['parts']) ||
            !is_array($page['parts'])
        ) {
            continue;
        }


        foreach ($page['parts'] as $part) {

            if (!isset($part['part_number'])) {
                continue;
            }


            $currentPartNumber = normalizePartNumber(
                (string)$part['part_number']
            );

            $currentName = normalizeName(
                (string)($part['name'] ?? '')
            );


            /**
             * Cikkszám keresés.
             */
            if ($currentPartNumber === $searchPartNumber) {

                $score = 30;

            } elseif (
                $searchPartNumber !== '' &&
                strpos($currentPartNumber, $searchPartNumber) === 0
            ) {

                $score = 20;

            } elseif (
                $searchPartNumber !== '' &&
                strpos($currentPartNumber, $searchPartNumber) !== false
            ) {

                $score = 10;


            /**
             * Név keresés.
             */
            } elseif (
                $currentName !== '' &&
                nameContainsAllWords($currentName, $searchName)
            ) {

                $score = 6;

            } else {

                continue;
            }


            $results[] = [

                'score'       => $score,

                'id'          => (string)($page['id'] ?? ''),

                'title'       => (string)($page['title'] ?? ''),

                'ref'         => (string)($part['ref'] ?? ''),

                'part_number' => (string)$part['part_number'],

                'name'        => (string)($part['name'] ?? ''),

                'qty'         => (string)($part['qty'] ?? ''),

            ];
        }
    }


    /**
     * Relevancia szerinti rendezés.
     *
     * 30 = pontos cikkszám
     * 20 = cikkszám elején egyezik
     * 10 = cikkszám egyéb részleges egyezés
     * 6  = névben minden keresett szó megtalálható
     */
    usort(
        $results,
        function ($a, $b) {

            return $b['score'] <=> $a['score'];
        }
    );
}

?>
						<table align="left" width="100%" class="table-border">
							<tr>
								<td class="textv-top" style="padding-bottom:40px;">
								<table align="left" width="100%" class="table-border">
									<?php if ($epcError !== ''): ?>
    								<tr>
        								<td style="padding:0px;text-align:center;">
            							<span class="epc-title"><?= htmlspecialchars($epcError) ?>
            							</span>
        								</td>
    								</tr>
									<?php elseif ($userCar !== null): ?>
    								<tr>
       						 			<td style="padding:0px;text-align:center;">
            							<span class="epc-title"><?php if (trim((string)($userCar['name'] ?? '')) !== ''): ?><?= htmlspecialchars($userCar['name']) ?> - <?php endif; ?><?= htmlspecialchars($userCar['vin']) ?> - EPC Kereső
            							</span>
        								</td>
    								</tr>
									<?php else: ?>
    								<tr>
        								<td style="padding:0px;text-align:center;">
            							<span class="epc-title"><?= htmlspecialchars($config['name']) ?> - EPC Kereső
            							</span>
        								</td>
    								</tr>
									<?php endif; ?>
									<?php if ($epcError === ''): ?>
									<tr>
										<td style="padding:20px;text-align:center;">
           								<form action="/epc/search.php" method="get">
										<table align="center">
											<tr>
												<td class="pr5"><input type="text" name="part_number" placeholder="Cikkszám vagy Alkatrész neve" required><input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>"><input type="hidden" name="model" value="<?= htmlspecialchars($model) ?>"><input type="hidden" name="series" value="<?= htmlspecialchars($series) ?>"><input type="hidden" name="model_code" value="<?= htmlspecialchars($modelCode) ?>"><input type="hidden" name="body_code" value="<?= htmlspecialchars($bodyCode) ?>"><input type="hidden" name="trim_code" value="<?= htmlspecialchars($trimCode) ?>"><?= $navigationInputs ?></td>
												<td><button type="submit">Keresés</button></td>
											</tr>
										</table>
										</form>
										</td>
									</tr>
									<tr>
										<td class="text-center pt10">
										<?php if ($partNumber === ''): ?>
										<span class='epc-text''>Adj meg egy cikkszámot vagy alkatrésznevet a kereséshez.</span>
										<?php elseif (empty($results)): ?>
										<span class='epc-text''>Nincs találat.<br><br></span>
										<span class='epc-text''>A keresett kifejezés: <b><?= htmlspecialchars($partNumber) ?></b> nem található a <?= htmlspecialchars($config['name']) ?> EPC-adatbázisában.</span>
				 						<?php else: ?>
                						<span class='epc-text''>Találatok: <?= count($results) ?></span>
                						<br><br>
                						<span class='epc-text''>Keresett kifejezés: <?= htmlspecialchars($partNumber) ?></span>
                						</td>
                					</tr>
                					<?php foreach ($results as $result): ?>
                					<tr>
                						<td width="100%" style="padding:20px;padding-bottom:0px;">
                    					<table class="table-100-center textv-top">
											<tr>
												<td style="background-color:#cccccc; padding:20px; padding-right:0px; width:300px;">
                                				<img src="<?= htmlspecialchars($config['image_dir'] . getImageName($result['id'], $config['extension'])) ?>" width="300">
                                				</td>
                            					<td style="background-color:#cccccc; padding:20px;" class="textv-top">
                            					<table class="table-100-center">
                            						<tr>
                            							<td class="epc-text" align="left" style="height: 30px;"><b>Termék kategória:</b></td><td class="epc-text" align="right"><?= htmlspecialchars($result['title']) ?></td>
                            						</tr>
                            						<tr>
                            							<td class="epc-text" align="left" style="height: 30px;"><b>Cikkszám:</b></td><td class="epc-text" align="right"><?= htmlspecialchars($result['part_number']) ?></td>
                            						</tr>
                            						<tr>
                            							<td class="epc-text" align="left" style="height: 30px;"><b>Megnevezés:</b></td><td class="epc-text" align="right"><?= htmlspecialchars($result['name']) ?></td>
                            						</tr>
                            						<tr>
                            							<td class="epc-text" align="left" style="height: 30px;"><b>Mennyiség:</b></td><td class="epc-text" align="right"><?= htmlspecialchars($result['qty'] ?: '') ?></td>
                            						</tr>
                            					</table>
                            					<table class="table-border" align="right">
                            						<tr>
                            							<td></td><td align="right" style="padding-top:0px;">
                            							<button type="button" onclick="window.location.href='/epc/view.php?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?>&body_code=<?= urlencode($bodyCode) ?>&trim_code=<?= urlencode($trimCode) ?>&page=<?= urlencode(strtolower($result['id'])) ?>&search=<?= urlencode($partNumber) ?><?= $navigationParams ?>'">Oldal megnyitása</button></td>
                            						</tr>
                            					</table>
                            					</td>
                        					</tr>
                    					</table>
            							</td>
            						</tr>
            						<?php endforeach; ?>
                						
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
