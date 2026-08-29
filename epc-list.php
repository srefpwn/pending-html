<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    header('Location: /index.php');
    exit;
}
require_once __DIR__ . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

/**
 * Melyik lista legyen?
 *
 * ?page=cn  -> CN
 * ?page=clr -> CLR
 */
$brand = strtolower(trim($_GET['brand'] ?? ''));
$model = strtolower(trim($_GET['model'] ?? ''));
$series = strtolower(trim($_GET['series'] ?? ''));
$modelCode = strtolower(trim($_GET['model_code'] ?? ''));

$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

/**
 * Ellenőrizzük, hogy létező EPC konfigurációt kértek-e.
 */
$config = null;

foreach ($configs as $item) {

    if (
        strtolower((string)($item['brand'] ?? '')) === $brand &&
        strtolower((string)($item['model'] ?? '')) === $model &&
        strtolower((string)($item['series'] ?? '')) === $series &&
        strtolower((string)($item['model_code'] ?? '')) === $modelCode
    ) {
        $config = $item;
        break;
    }
}

if ($config === null) {
    echo '<p>Érvénytelen EPC konfiguráció.</p>';
    return;
}


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

    /*
     * Az autó konfigurációjának lekérése
     */
    if ($userCar !== null) {

        $userCarConfig = getCarConfig($userCar);

        /*
         * Ellenőrizzük, hogy az autó valóban
         * ehhez az EPC oldalhoz tartozik-e.
         */
        if (
            $userCarConfig === null ||
           ($userCarConfig['epc_page'] ?? '') !== $modelCode
        ) {
            $userCar = null;
            $userCarConfig = null;
        }

    }
}

/**
 * JSON betöltése
 */
$lista = json_decode(
    file_get_contents($config['lista_json']),
    true
);


/**
 * Ha a JSON nem tölthető be vagy hibás.
 */
if (!is_array($lista)) {
    echo '<p>A lista betöltése sikertelen.</p>';
    return;
}
/**
 * Kategóriák kigyűjtése a JSON-ból
 */
$categories = [];

foreach ($lista as $elem) {
    if (!empty($elem['category'])) {
        $categories[] = (string)$elem['category'];
    }
}

$categories = array_unique($categories);


/**
 * Kiválasztott kategória
 */
$selectedCategory = $_GET['category'] ?? 'all';


/**
 * Lista szűrése
 */
if ($selectedCategory !== 'all') {
    $lista = array_filter($lista, function ($elem) use ($selectedCategory) {
        return isset($elem['category'])
            && $elem['category'] === $selectedCategory;
    });
}

$lista = array_values($lista);



?>
			<table id="epc-list" align="left" width="100%" class="table-border">
				<tr>
					<td class="textv-top">
					<table align="left" class="table-border">
					<?php if ($userCar !== null): ?>
						<tr>
    						<td style="padding:0px;text-align:center;">
							<span class="epc-title">
							<?php if (trim((string)($userCar['name'] ?? '')) !== ''): ?>
           					<?= htmlspecialchars($userCar['name']) ?> - 
        					<?php endif; ?>
        					<?= htmlspecialchars($userCar['vin']) ?> - 
        					<?php if (trim((string)($selectedCategory ?? '')) !== '' && $selectedCategory !== 'all'): ?><?= htmlspecialchars($selectedCategory) ?> - <?php endif; ?>
							EPC
							</span>
    						</td>
						</tr>
					<?php else: ?>
						<tr>
    						<td style="padding:0px;text-align:center;">
        					<span class="epc-title">
        					<?= htmlspecialchars($config['name']) ?> - 
        					<?php if (trim((string)($selectedCategory ?? '')) !== '' && $selectedCategory !== 'all'): ?><?= htmlspecialchars($selectedCategory) ?> - <?php endif; ?>
        					EPC
        					</span>
        					</td>
						</tr>
					<?php endif; ?>
						<tr>
							<td style="padding:20px;text-align:center;">
							<form action="/epc/search.php" method="get">
							<table align="center">
								<tr>
									<td class="pr5"><input type="text" name="part_number" placeholder="Cikkszám vagy Alkatrész neve" required><input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>"><input type="hidden" name="model" value="<?= htmlspecialchars($model) ?>"><input type="hidden" name="series" value="<?= htmlspecialchars($series) ?>"><input type="hidden" name="model_code" value="<?= htmlspecialchars($modelCode) ?>"><?= $navigationInputs ?></td>
									<td><button type="submit">Keresés</button></td>
								</tr>
							</table>
							</form>
							</td>
						</tr>
						<tr>
							<td style="padding:20px;text-align:center;">
							<table align="center">
								<tr>
									<td>
									<a href="?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?><?= $navigationParams ?>&category=all" data-category="all" class="<?= $selectedCategory !== 'all' ? 'greybutton' : '' ?>"><button type="submit">Összes</button></a>
									</td>
           							<?php foreach ($categories as $category): ?>
                					<td>
                    				<a href="?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?><?= $navigationParams ?>&category=<?= urlencode($category) ?>" data-category="<?= htmlspecialchars($category) ?>" class="<?= $selectedCategory !== $category ? 'greybutton' : '' ?>"><button type="submit"><?= htmlspecialchars($category) ?></button></a>
                					</td>
            						<?php endforeach; ?>
								</tr>
							</table>
							</td>
						</tr>
<?php endif; ?>
						<tr>
							<td class="textv-top">				
							<table class="menutable">
<?php

/**
 * Képnév generálás
 *
 * Az első aláhúzásos eset mindkét rendszernél:
 * B_1 -> B_1_.png
 *
 * Egyéb eset:
 * CN  -> .gif
 * CLR -> .png
 */
function getImageName(string $id, string $extension): string
{
    if (substr_count($id, '_') === 1) {
        return $id . '_.png';
    }

    return $id . '.' . $extension;
}


/**
 * Oldal link generálás
 */
function getHref(
    string $brand,
    string $model,
    string $series,
    string $modelCode,
    string $id
): string {

    return '/epc/view.php'
        . '?brand=' . urlencode($brand)
        . '&model=' . urlencode($model)
        . '&series=' . urlencode($series)
        . '&model_code=' . urlencode($modelCode)
        . '&page=' . urlencode(strtolower($id));
}


foreach ($lista as $i => $elem) {

    /**
     * Új sor minden harmadik elem előtt.
     */
    if ($i % 4 === 0) {
        echo '<tr>';
    }


    $id = (string)($elem['id'] ?? '');
    $title = (string)($elem['title'] ?? '');


    $href = getHref(
    $brand,
    $model,
    $series,
    $modelCode,
    $id
);

    $image = $config['image_dir']
           . getImageName($id, $config['extension']);
           
    ?>
    <td>
        <a href="<?= htmlspecialchars($href) ?><?= $navigationParams ?>" class="epc">
            <?= htmlspecialchars($id) ?>
            <?= htmlspecialchars(mb_convert_case($title, MB_CASE_TITLE, 'UTF-8')) ?>
            <br><br>
            <img src="<?= htmlspecialchars($image) ?>" width="80%">
        </a>
    </td>

    <?php


    /**
     * Harmadik elem után lezárjuk a sort.
     */
    if (($i + 1) % 4 === 0) {
        echo '</tr>';
    }
}


/**
 * Ha az utolsó sorban nincs meg a 3 elem,
 * feltöltjük üres cellákkal.
 */
$maradek = count($lista) % 4;

if ($maradek !== 0) {

    for ($i = $maradek; $i < 4; $i++) {
        echo '<td></td>';
    }

    echo '</tr>';
}

?>
							</table>
							</td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
