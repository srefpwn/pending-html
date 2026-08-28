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
$page = strtolower($_GET['page'] ?? '');
$type = $page;

$carId = filter_input(
    INPUT_GET,
    'car',
    FILTER_VALIDATE_INT
);

/**
 * Ellenőrizzük, hogy létező típust kértek-e.
 */
if (!isset($configs[$page])) {
    echo '<p>Érvénytelen lista.</p>';
    return;
}


$config = $configs[$page];
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
            ($userCarConfig['epc_page'] ?? '') !== $page
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
			<table align="left" width="100%" class="table-border">
				<tr>
					<td class="textv-top">
					<table align="left" class="table-border">
					<?php if ($page === 'cn1' || $page === 'clr'): ?>
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
									<td class="pr5"><input type="text" name="part_number" placeholder="Cikkszám vagy Alkatrész neve" required><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?= $navigationInputs ?></td>
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
									<a href="?page=<?= urlencode($page) ?><?= $navigationParams ?>&category=all" data-category="all" class="<?= $selectedCategory !== 'all' ? 'greybutton' : '' ?>"><button type="submit">Összes</button></a>
									</td>
           							<?php foreach ($categories as $category): ?>
                					<td>
                    				<a href="?page=<?= urlencode($page) ?><?= $navigationParams ?>&category=<?= urlencode($category) ?>" data-category="<?= htmlspecialchars($category) ?>" class="<?= $selectedCategory !== $category ? 'greybutton' : '' ?>"><button type="submit"><?= htmlspecialchars($category) ?></button></a>
                					</td>
            						<?php endforeach; ?>
								</tr>
							</table>
							</td>
						</tr>
<?php endif; ?>
						<tr>
							<td class="textv-top">	ű
<?php
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    ob_start();
}
?>					
							<table id="epc-list" class="menutable">
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
function getHref(string $type, string $id): string
{
    return '/epc/view.php?type=' . urlencode($type) . '&page=' . urlencode(strtolower($id));
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


    $href = getHref($page, $id);

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
<?php

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {

    $html = ob_get_clean();

    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'html' => $html,
        'category' => $selectedCategory
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const buttons = document.querySelectorAll('a[data-category]');

    if (!buttons.length) {
        return;
    }


    /*
     * Kategória betöltése AJAX-szal
     */
    function loadCategory(category, updateUrl = true) {

        const currentUrl = new URL(window.location.href);

        /*
         * Megőrizzük az összes jelenlegi URL-paramétert.
         * Csak a category értékét módosítjuk.
         */
        currentUrl.searchParams.set('category', category);
        currentUrl.searchParams.set('ajax', '1');

        fetch(currentUrl.pathname + '?' + currentUrl.searchParams.toString())
            .then(function (response) {

                if (!response.ok) {
                    throw new Error('A kérés sikertelen.');
                }

                return response.json();
            })
            .then(function (data) {

                /*
                 * A PHP a teljes EPC táblát küldi vissza.
                 */
                const oldList = document.getElementById('epc-list');

                if (!oldList) {
                    return;
                }

                oldList.outerHTML = data.html;


                /*
                 * Kategória gombok állapotának frissítése.
                 */
                buttons.forEach(function (button) {

                    if (button.dataset.category === String(data.category)) {
                        button.classList.remove('greybutton');
                    } else {
                        button.classList.add('greybutton');
                    }

                });


                /*
                 * URL frissítése oldalbetöltés nélkül.
                 */
                if (updateUrl) {

                    const cleanUrl = new URL(window.location.href);

                    cleanUrl.searchParams.set(
                        'category',
                        data.category
                    );

                    cleanUrl.searchParams.delete('ajax');

                    history.pushState(
                        { category: data.category },
                        '',
                        cleanUrl.pathname + '?' + cleanUrl.searchParams.toString()
                    );
                }

            })
            .catch(function (error) {

                console.error(error);

                /*
                 * Ha az AJAX nem sikerül,
                 * visszatérünk a normál GET-es működéshez.
                 */
                const button = Array.from(buttons).find(function (item) {
                    return item.dataset.category === String(category);
                });

                if (button) {
                    window.location.href = button.href;
                }

            });
    }


    /*
     * Kategóriagombok kattintása
     */
    buttons.forEach(function (button) {

        button.addEventListener('click', function (event) {

            event.preventDefault();

            const category = button.dataset.category;

            if (!category) {
                return;
            }

            loadCategory(category);

        });

    });


    /*
     * Böngésző Vissza / Előre gomb kezelése
     */
    window.addEventListener('popstate', function () {

        const params = new URLSearchParams(window.location.search);
        const category = params.get('category') || 'all';

        loadCategory(category, false);

    });

});
</script>
							</td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
