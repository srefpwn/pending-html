<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once __DIR__ . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

$type = strtolower(trim($_GET['type'] ?? ''));
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

        $userCarConfig = getCarConfig($userCar);

        if (
            $userCarConfig === null ||
            ($userCarConfig['epc_page'] ?? '') !== $type
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
if (!isset($configs[$type])) {
    http_response_code(400);
    exit('Érvénytelen keresési típus.');
}


$config = $configs[$type];


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
$epcFile = $config['epc_json'];


if (!is_file($epcFile)) {
    http_response_code(500);
    exit('Az EPC adatfájl nem található.');
}


$json = file_get_contents($epcFile);

$epc = json_decode($json, true);


/**
 * Ellenőrizzük a JSON-t.
 */
if (!is_array($epc)) {
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
if ($partNumber !== '') {

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
									<?php if ($userCar !== null): ?>
									<tr>
    									<td style="padding:0px;text-align:center;">
										<span class="epc-title">
										<?php if (trim((string)($userCar['name'] ?? '')) !== ''): ?>
           								<?= htmlspecialchars($userCar['name']) ?> - 
        								<?php endif; ?>
        								<?= htmlspecialchars($userCar['vin']) ?> - 
										EPC Kereső
										</span>
    									</td>
									</tr>
									<?php else: ?>
									<tr>
    									<td style="padding:0px;text-align:center;">
        								<span class="epc-title">
        								<?= htmlspecialchars($config['name']) ?> - EPC Kereső
        								</span>
        								</td>
									</tr>
									<?php endif; ?>
									<tr>
										<td style="padding:20px;text-align:center;">
           								<form action="/epc/search.php" method="get">
										<table align="center">
											<tr>
												<td class="pr5"><input type="text" name="part_number" placeholder="Cikkszám vagy Alkatrész neve" required><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?= $navigationInputs ?></td>
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
                						<?php foreach ($results as $result): ?>
                						</td>
                					</tr>
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
                            							<button type="button" onclick="window.location.href='/epc/view.php?type=<?= urlencode($type) ?>&page=<?= urlencode(strtolower($result['id'])) ?>&search=<?= urlencode($partNumber) ?><?= $navigationParams ?>'">Oldal megnyitása</button></td>
                            						</tr>
                            					</table>
                            					</td>
                        					</tr>
                    					</table>
                						<?php endforeach; ?>
            							<?php endif; ?>
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
