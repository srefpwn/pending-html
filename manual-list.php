<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/functions.php';

/*
 * Manual típusa és évjárata
 *
 * Példa:
 * /manual/?type=cn1&year=2006
 */

$brand = strtolower(
    trim((string)($_GET['brand'] ?? ''))
);

$model = strtolower(
    trim((string)($_GET['model'] ?? ''))
);

$series = strtolower(
    trim((string)($_GET['series'] ?? ''))
);

$modelCode = strtolower(
    trim((string)($_GET['model_code'] ?? ''))
);

$manualConfig = null;

foreach ($configs as $item) {

    if (
        ($item['brand'] ?? '') === $brand &&
        ($item['model'] ?? '') === $model &&
        ($item['series'] ?? '') === $series &&
        ($item['model_code'] ?? '') === $modelCode
    ) {
        $manualConfig = $item;
        break;
    }
}

$year = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT
);


/*
 * Alapadatok ellenőrzése
 */

if (
    $manualConfig === null ||
    $year === false ||
    $year === null ||
    $year <= 0
) {
    echo '<p>Érvénytelen manual lista.</p>';
    return;
}


/*
 * Manual lista JSON útvonala
 */

$book = strtolower(
    trim((string)($_GET['book'] ?? 'manual'))
);

if (!in_array($book, ['manual', 'body'], true)) {
    $book = 'manual';
}

$listaFile = getManualListFile(
    $manualConfig,
    $year,
    $book
);

/*
 * JSON fájl ellenőrzése
 */

if (!is_file($listaFile)) {
    echo '<p>A kiválasztott manual lista nem található.</p>';
    return;
}


/*
 * JSON betöltése
 */

$manualData = json_decode(
    file_get_contents($listaFile),
    true
);


/*
 * JSON ellenőrzése
 */

if (
    !is_array($manualData) ||
    !isset($manualData['categories']) ||
    !is_array($manualData['categories']) ||
    count($manualData['categories']) === 0
) {
    echo '<p>A manual lista betöltése sikertelen.</p>';
    return;
}


/*
 * Összes kategória
 */

$categories = $manualData['categories'];

/*
 * Keresés
 */
$search = trim((string)($_GET['search'] ?? ''));
$isSearch = ($search !== '');

$searchResults = [];
/*
 * Kategória kiválasztása
 *
 * Ha nincs megadva:
 * az első kategória lesz az aktív.
 */

$selectedCategoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT
);


/*
 * Ha nincs érvényes category,
 * akkor az első kategória ID-ját használjuk.
 */

if (!$isSearch && (
    $selectedCategoryId === false ||
    $selectedCategoryId === null
)) {
    $selectedCategoryId =
        (int)($categories[0]['id'] ?? 0);
}

/*
 * Kiválasztott kategória keresése
 */

$selectedCategory = null;

foreach ($categories as $category) {

    $categoryId =
        (int)($category['id'] ?? 0);

    if ($categoryId === $selectedCategoryId) {

        $selectedCategory = $category;

        break;
    }
}


/*
 * Ha a megadott kategória nem létezik,
 * visszaesünk az első kategóriára.
 */

if (!$isSearch && $selectedCategory === null) {

    $selectedCategory =
        $categories[0];

    $selectedCategoryId =
        (int)($selectedCategory['id'] ?? 0);
}

/*
 * A kiválasztott kategória témái
 */

$pages = [];

if (!$isSearch) {

    $pages =
        $selectedCategory['pages'] ?? [];

    if (!is_array($pages)) {
        $pages = [];
    }

} else {

    /*
     * Keresés az összes kategória összes oldalában.
     */
    foreach ($categories as $category) {

        $categoryId =
            (int)($category['id'] ?? 0);

        $categoryPages =
            $category['pages'] ?? [];

        if (!is_array($categoryPages)) {
            continue;
        }

        foreach ($categoryPages as $page) {

            $name =
                (string)($page['name'] ?? '');

            $id =
                (string)($page['id'] ?? '');

            if ($id === '') {
                continue;
            }

            if (
                mb_stripos(
                    $name,
                    $search,
                    0,
                    'UTF-8'
                ) !== false
            ) {

                $searchResults[] = [
                    'id' => $id,
                    'name' => $name,
                    'category_id' => $categoryId
                ];
            }
        }
    }
}
/*
 * Aktuális autó adatai
 */

$carName = '';
$vin = '';

$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

if (
    $carId !== false &&
    $carId !== null &&
    $carId > 0
) {

    $userCars = getUserCars();

    foreach ($userCars as $userCar) {

        if (
            isset($userCar['id']) &&
            (int)$userCar['id'] === $carId
        ) {

            $carName = trim(
                (string)($userCar['name'] ?? '')
            );

            $vin = (string)(
                $userCar['vin'] ?? ''
            );

            break;
        }
    }
}
?>
						<table align="left" width="100%" class="table-border">
                            <tr>
                                <td class="textv-top">
                                <table align="center" class="table-border" width="100%">
                                    <tr>
                                        <td style="padding:0px;text-align:center;">
                                        <span class="epc-title">
                                        <?php if ($carName !== ''): ?>
                                        <?= htmlspecialchars($carName) ?> - <?= htmlspecialchars($vin) ?> - 
                                        <?php endif; ?>
                                        <?= htmlspecialchars( $isSearch ? 'Keresés: ' . $search : (string)($selectedCategory['name'] ?? '')) ?> - <?= $book === 'body' ? 'Karosszéria-javítási kézikönyv' : 'Szerviz kézikönyv' ?>
                                        </span>
                                        </td>
                                    </tr>
                				<!-- Keresés -->
               						<tr>
                    					<td style="padding:20px;text-align:center;">
                        				<form action="/manual/" method="get">
                            			<table align="center">
                                			<tr>
                                				<td class="pr5">
                                        		<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Keresés a manualban" required>
                                        		<input type="hidden" name="brand" value="<?= htmlspecialchars($brand) ?>">
												<input type="hidden" name="model" value="<?= htmlspecialchars($model) ?>">
												<input type="hidden" name="series" value="<?= htmlspecialchars($series) ?>">
												<input type="hidden" name="model_code" value="<?= htmlspecialchars($modelCode) ?>">
                                        		<?= $navigationInputs ?>
                                    			</td>
                                    			<td>
                                        		<button type="submit">Keresés
                                        		</button>
                                    			</td>
                                			</tr>
                            			</table>
                        				</form>
                    					</td>
                					</tr>
									<tr>
										<td style="padding:20px;text-align:center;">
										<table id="manual-book" align="center">
											<tr>
   			 									<td>
        										<a href="?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?>&year=<?= (int)$year ?><?= $navigationParams ?>&book=manual" data-book="manual" class="<?= $book !== 'manual' ? 'greybutton' : '' ?>">
        										<button type="submit">Javítási könyvek</button>
        										</a>
    											</td>
												<td>
        										<a href="?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?>&year=<?= (int)$year ?><?= $navigationParams ?>&book=body" data-book="body" class="<?= $book !== 'body' ? 'greybutton' : '' ?>">
        										<button type="submit">Karosszéria-javítási könyvek</button>
        										</a>
    											</td>
											</tr>
										</table>
										</td>
									</tr>
                                    <tr>
                                        <td class="textv-top">
                                        <table class="menutable" width="100%">
                        					<tr>
                        						<td class="textv-top" width="200">
                        						<table id="manual-category" class="table-border">
                        							
                                						<?php foreach ($categories as $category): ?>
                                    					<?php
                                    					$categoryId =
                                        				(int)($category['id'] ?? 0);

                                    					$categoryName =
                                        				(string)($category['name'] ?? '');
                                        				
                                        				$isActive =
  												 	 	!$isSearch &&
    													($categoryId === $selectedCategoryId);
                                    					?>
                                    				<tr>
                                    					<td>
                                        				<a href="?brand=<?= urlencode($brand) ?>&model=<?= urlencode($model) ?>&series=<?= urlencode($series) ?>&model_code=<?= urlencode($modelCode) ?>&year=<?= (int)$year ?><?= $navigationParams ?>&category=<?= $categoryId ?>&book=<?= urlencode($book) ?>" data-category="<?= $categoryId ?>" class="<?= $isActive ? '' : 'greybutton' ?>">
                                            			<button type="button" style="width:200px"><?= htmlspecialchars($categoryName) ?>
                                            			</button>
                                        				</a>
                                    					</td>
                                    				</tr>
                                						<?php endforeach; ?>
                        							
                        						</table>
                        						</td>
                        						<td class="textv-top">
                        						<table id="manual-list" class="table-border" width="100%">
                        			
<?php

/*
 * A kiválasztott kategória témáinak megjelenítése.
 *
 * Minden téma külön sorban jelenik meg.
 * A sorok háttérszíne váltakozik.
 */

$rowIndex = 0;

$listItems = $isSearch
    ? $searchResults
    : $pages;

foreach ($listItems as $page):

$id =
    (string)($page['id'] ?? '');

$name =
    (string)($page['name'] ?? '');

$itemCategoryId = $isSearch
    ? (int)($page['category_id'] ?? 0)
    : (int)$selectedCategoryId;

    if ($id === '') {
        continue;
    }


    /*
     * A konkrét manual oldal linkje.
     */

    $href =
        '/manual/view.php'
        . '?type='
        . urlencode($type)
        . '&year='
        . (int)$year
. '&category='
. $itemCategoryId
        . '&id='
        . urlencode($id);


    /*
     * Váltakozó háttérszín.
     *
     * Az első sor row-even,
     * a második row-odd.
     */

    $rowClass =
        ($rowIndex % 2 === 0)
        ? 'row-even'
        : 'row-odd';

?>

<tr>
    <td class="<?= $rowClass ?>">
        <a
            href="<?= htmlspecialchars($href) ?><?= $navigationParams ?>"
            class="manual"
        >
            <?= htmlspecialchars($name) ?>
        </a>
    </td>
</tr>

<?php

    $rowIndex++;

endforeach;

?>

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
