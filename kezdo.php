<?php

if (!defined('APP_INIT')) {
    http_response_code(403);
    header('Location: /index.php');
    exit;
}


/*
 * Manual típusa és évjárata
 *
 * Példa:
 * /manual/?type=cn1&year=2006
 */

$type = strtolower(
    trim((string)($_GET['type'] ?? ''))
);

$year = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT
);


/*
 * Alapadatok ellenőrzése
 */

if (
    $type === '' ||
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

$listaFile =
    __DIR__
    . '/data/'
    . $type
    . '/'
    . $year
    . '/manual-list.json';


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

if (
    $selectedCategoryId === false ||
    $selectedCategoryId === null
) {
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

if ($selectedCategory === null) {

    $selectedCategory =
        $categories[0];

    $selectedCategoryId =
        (int)($selectedCategory['id'] ?? 0);
}


/*
 * A kiválasztott kategória témái
 */

$pages =
    $selectedCategory['pages'] ?? [];


if (!is_array($pages)) {
    $pages = [];
}
?>
            <tr>
                <td align="left">
                <table style="width:100%;">
                    <tr>
                        <td>
                        <table align="left" width="100%" class="table-border">
                            <tr>
                                <td class="textv-top">
                                <table align="left" class="table-border" width="100%">
                                    <tr>
                                        <td style="padding:0px;text-align:center;">
                                        <span class="epc-title">
    									<?= htmlspecialchars(strtoupper($type)) ?> -
    									<?= htmlspecialchars((string)$year) ?> -
    									<?= htmlspecialchars((string)($selectedCategory['name'] ?? '')) ?> - Manual
										</span>
                                        </td>
                                    </tr>
                				<!-- Keresés -->
               						<tr>
                    					<td style="padding:20px;text-align:center;">
                        				<form action="/manual/search.php" method="get">
                            			<table align="center">
                                			<tr>
                                				<td class="pr5">
                                        		<input type="text" name="q" placeholder="Keresés a manualban" required>
                                        		<input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                        		<input type="hidden" name="year" value="<?= (int)$year ?>">
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
                                        <td class="textv-top">
                                        <table class="menutable" width="100%">
                        					<tr>
                        						<td class="textv-top" width="200">
                        						<table class="table-border">
                        							
                                						<?php foreach ($categories as $category): ?>
                                    					<?php
                                    					$categoryId =
                                        				(int)($category['id'] ?? 0);

                                    					$categoryName =
                                        				(string)($category['name'] ?? '');
                                    					?>
                                    				<tr>
                                    					<td>
                                        				<a href="?type=<?= urlencode($type) ?>&year=<?= (int)$year ?><?= $navigationParams ?>&category=<?= $categoryId ?>">
                                            			<button type="button"><?= htmlspecialchars($categoryName) ?>
                                            			</button>
                                        				</a>
                                    					</td>
                                    				</tr>
                                						<?php endforeach; ?>
                        							
                        						</table>
                        						</td>
                        						<td class="textv-top">
                        						<table class="table-border" width="100%">
                        			
<?php

/*
 * A kiválasztott kategória témáinak megjelenítése.
 *
 * Minden téma külön sorban jelenik meg.
 * A sorok háttérszíne váltakozik.
 */

$rowIndex = 0;

foreach ($pages as $page):

    $id =
        (string)($page['id'] ?? '');

    $name =
        (string)($page['name'] ?? '');

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
        . (int)$selectedCategoryId
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
