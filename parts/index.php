<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/parts/functions.php';

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
$carConfig = getCarConfig($car);

if ($carConfig === null || !isCarPartsEnabled($carConfig)) {
    denyAccess();
}

/*
 * Az autó adatai a meglévő adatforrásból.
 */
$carName = trim((string)($car['name'] ?? ''));
$vin = (string)($car['vin'] ?? '');

$partsLoadError = false;
$parts = [];

try {
    $parts = loadCarParts((int)$userId, (int)$carId);
} catch (RuntimeException $e) {
    $partsLoadError = true;
}


$partCategories = [];

foreach ($parts as $part) {
    $category = trim((string)($part['category'] ?? ''));

    if ($category !== '') {
        $partCategories[$category] = $category;
    }
}

$partCategories = array_values($partCategories);
natcasesort($partCategories);
$partCategories = array_values($partCategories);


/*
 * AJAX-os alkatrészszűrés
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_GET['ajax'] ?? '') === 'filter'
) {
    header('Content-Type: text/html; charset=UTF-8');

    $selectedCategory = trim((string)($_POST['category'] ?? ''));
    $search = trim((string)($_POST['search'] ?? ''));

    $filteredParts = [];

    foreach ($parts as $part) {
        $category = (string)($part['category'] ?? '');
        $partNumber = (string)($part['part_number'] ?? '');
        $partName = (string)($part['name'] ?? '');

        if (
            $selectedCategory !== '' &&
            $category !== $selectedCategory
        ) {
            continue;
        }

        if ($search !== '') {
            $searchText = $partNumber . ' ' . $partName;

            $matches = function_exists('mb_stripos')
                ? mb_stripos($searchText, $search, 0, 'UTF-8') !== false
                : stripos($searchText, $search) !== false;

            if (!$matches) {
                continue;
            }
        }

        $filteredParts[] = $part;
    }

    if (empty($filteredParts)) {
        echo '<tr><td colspan="6" class="p5 text-center">';
        echo 'Nincs a feltételeknek megfelelő alkatrész.';
        echo '</td></tr>';
        exit;
    }

    foreach ($filteredParts as $index => $part) {
        $rowClass = ($index % 2 === 0) ? 'row-even' : 'row-odd';

        $category = (string)($part['category'] ?? '');
        $partNumber = (string)$part['part_number'];
        $partName = (string)$part['name'];
        $quantity = (float)$part['quantity'];
		$quantityDisplay = rtrim(rtrim(number_format($quantity, 1, ',', ''), '0'), ',');
		$unit = (string)($part['unit'] ?? 'db');
        $partId = (int)$part['id'];
        ?>
        <tr
            data-part-row
            data-category="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
        >
            <td class="pl10 epc-text <?= $rowClass ?> p5 text-left" width="20%"><?= htmlspecialchars($partNumber, ENT_QUOTES, 'UTF-8') ?></td>

            <td class="pl10 epc-text <?= $rowClass ?> p5 text-left"><?= htmlspecialchars($partName, ENT_QUOTES, 'UTF-8') ?></td>

            <td class="epc-text <?= $rowClass ?> p5 text-center part-current-quantity" width="7%"><?= htmlspecialchars($quantityDisplay . ' ' . $unit, ENT_QUOTES, 'UTF-8') ?></td>

            <td class="epc-text <?= $rowClass ?> p5 text-center" width="7%">
            <input type="text" inputmode="decimal" class="part-quantity-input" data-current="<?= $quantity ?>" data-unit="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>" data-part-id="<?= $partId ?>" placeholder="<?= $quantity ?>" style="width:60px;" autocomplete="off">
            </td>

            <td class="<?= $rowClass ?> greybutton" width="140">
                <button type="button" class="part-save-button" disabled style="width:140px">Mentés</button>
            </td>

            <td class="<?= $rowClass ?>" width="140">
                <button style="width:140px" type="button" class="part-delete-button">Törlés</button>
            </td>
        </tr>
        <?php
    }

    exit;
}


$backUrl = '/cars/';

if (
    isset($_GET['back']) &&
    is_string($_GET['back']) &&
    $_GET['back'] !== ''
) {
    $backUrl = $_GET['back'];
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
		


<script>
document.addEventListener('input', function (event) {
    if (!event.target.classList.contains('part-quantity-input')) {
        return;
    }

    const input = event.target;
    const value = input.value.trim();
    const current = Number(input.dataset.current);
    const row = input.closest('tr');
    const button = row.querySelector('.part-save-button');
    const cell = button.closest('td');

const unit = input.dataset.unit || 'db';

const validFormat = unit === 'db'
    ? /^[0-9]+$/.test(value)
    : /^[0-9]+(?:[.,][0-9])?$/.test(value);

const newQuantity = Number(value.replace(',', '.'));

const canSave =
    validFormat &&
    Number.isFinite(newQuantity) &&
    newQuantity > 0 &&
    newQuantity !== current;

    button.disabled = !canSave;
    cell.classList.toggle('greybutton', !canSave);
});

document.addEventListener('click', async function (event) {
    const button = event.target.closest('.part-save-button');

    if (!button || button.disabled) {
        return;
    }

    const row = button.closest('tr');
    const input = row.querySelector('.part-quantity-input');
    const quantityCell = row.querySelector('.part-current-quantity');
    const saveCell = button.closest('td');

    if (!input || !quantityCell || !saveCell) {
        return;
    }

const quantityText = input.value.trim();
const unit = input.dataset.unit || 'db';

const validFormat = unit === 'db'
    ? /^[0-9]+$/.test(quantityText)
    : /^[0-9]+(?:[.,][0-9])?$/.test(quantityText);

if (!validFormat) {
    return;
}

const quantity = Number(quantityText.replace(',', '.'));
const currentQuantity = Number(input.dataset.current);

if (
    !Number.isFinite(quantity) ||
    quantity <= 0 ||
    quantity === currentQuantity
) {
    return;
}

    const data = new URLSearchParams();

    data.set('car', <?= json_encode((int)$carId) ?>);
    data.set('part_id', input.dataset.partId);
    data.set('quantity', String(quantity));

    <?php if (isset($_GET['user_id'])): ?>
    data.set('user_id', <?= json_encode((int)$userId) ?>);
    <?php endif; ?>

    button.disabled = true;
    button.textContent = 'Mentés...';

    try {
        const response = await fetch('/parts/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString()
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'A mentés sikertelen.'
            );
        }

        const unit = input.dataset.unit || 'db';

const quantityDisplay = Number(result.quantity).toLocaleString(
    'hu-HU',
    { maximumFractionDigits: 1 }
);

quantityCell.textContent = quantityDisplay + ' ' + unit;
        input.dataset.current = String(result.quantity);
        input.placeholder = String(result.quantity);
        input.value = '';

        saveCell.classList.add('greybutton');
        button.disabled = true;
    } catch (error) {
        alert(error.message || 'Hiba történt a mentés során.');
    } finally {
        button.textContent = 'Mentés';


const value = input.value.trim();

const current = Number(input.dataset.current);
const unit = input.dataset.unit || 'db';

const validFormat = unit === 'db'
    ? /^[0-9]+$/.test(value)
    : /^[0-9]+(?:[.,][0-9])?$/.test(value);

const newQuantity = Number(value.replace(',', '.'));

const canSave =
    validFormat &&
    Number.isFinite(newQuantity) &&
    newQuantity > 0 &&
    newQuantity !== current;

button.disabled = !canSave;

saveCell.classList.toggle('greybutton', !canSave);

    }
});

document.addEventListener('click', async function (event) {
    const button = event.target.closest('.part-delete-button');

    if (!button || button.disabled) {
        return;
    }

    if (!confirm('Biztosan törlöd ezt az alkatrészt?')) {
        return;
    }

    const row = button.closest('tr');
    const input = row.querySelector('.part-quantity-input');
    const partId = input?.dataset.partId;

    if (!partId) {
        alert('Nem található az alkatrész azonosítója.');
        return;
    }

    const data = new URLSearchParams();

    data.set('car', <?= json_encode((int)$carId) ?>);
    data.set('part_id', partId);

    <?php if (isset($_GET['user_id'])): ?>
    data.set('user_id', <?= json_encode((int)$userId) ?>);
    <?php endif; ?>

    button.disabled = true;

    try {
        const response = await fetch('/parts/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString()
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || 'A törlés sikertelen.'
            );
        }

        row.remove();

		const tbody = document.querySelector('.parts-list tbody');

        if (tbody && !tbody.querySelector('tr[data-part-row]')) {
            const emptyRow = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 5;
            cell.textContent = 'Még nincsenek alkatrészek hozzáadva.';
            cell.className = 'p5 text-center';

            emptyRow.appendChild(cell);
            tbody.appendChild(emptyRow);
        }
    } catch (error) {
        button.disabled = false;
        alert(error.message || 'Hiba történt a törlés során.');
    }
});

document.addEventListener('DOMContentLoaded', function () {
const categoryFilter = document.getElementById('part_category_filter');
const searchInput = document.getElementById('part_search');
const filterButton = document.getElementById('part_filter_button');

let filterTimer;

async function filterParts() {
    const table = document.querySelector('.parts-list');

    if (!table) {
        return;
    }

    const data = new URLSearchParams();

    data.set('category', categoryFilter.value);
    data.set('search', searchInput.value.trim());

    const url = new URL(window.location.href);
    url.searchParams.set('ajax', 'filter');

    filterButton.disabled = true;

    try {
        const response = await fetch(url.toString(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString()
        });

        if (!response.ok) {
            throw new Error('A szűrés sikertelen.');
        }

        const html = await response.text();
        const tbody = table.querySelector('tbody');

        if (tbody) {
            tbody.innerHTML = html;
        }
    } catch (error) {
        alert(error.message || 'Hiba történt a szűrés során.');
    } finally {
        filterButton.disabled = false;
    }
}

filterButton.addEventListener('click', filterParts);

});

</script>


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
                  		</table>
                  		<table style="width:100%;" class="table-border">
							<tr>
								<td style="padding:20px; text-align:center;">
								<table class="table-border text-center">
									<tr>
										<td>
										<a href="/parts/add.php?car=<?= (int)$carId ?><?= isset($_GET['user_id']) && isAdmin() ? '&user_id=' . (int)$_GET['user_id'] : '' ?>">
    									<button type="button">Alkatrész hozzáadása</button>
										</a>
										</td>
									</tr>
								</table>
								</td>
							</tr>
							<tr>
								<td style="padding:20px; text-align:center;">
								<table class="table-border text-center">
									<tr>
										<td style="padding-left:5px;padding-right:5px;background-color:#cccccc;">
										<span class="select-wrapper2">
										<select id="part_category_filter" style="width:170px;">
    										<option value="">Összes kategória</option>
    										<?php foreach ($partCategories as $category): ?>
        									<option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></option>
    										<?php endforeach; ?>
										</select>
										</span>
										</td>
										<td style="padding-right:5px;background-color:#cccccc;">
										<input type="text" id="part_search" placeholder="Cikkszám vagy megnevezés" style="width:170px;">
										</td>
										<td style="padding-left:5px;">
										<button type="button" id="part_filter_button">Szűrés</button>
										</td>
									</tr>
								</table>
								</td>
							</tr>
                            <!--
                             * Üzenet
                            -->
                            <?php if ($partsLoadError): ?>
                            <tr>
                                <td style="padding:20px;text-align:center;">
                                <span style="font-size:14px;color:red;">
                                Az alkatrészlista betöltése sikertelen. Kérjük, próbáld meg később.
                                </span>
                                </td>
                        	</tr>
                           	<?php elseif (empty($parts)): ?>
                            <tr>
                                <td style="padding:20px;text-align:center;">
                                <span style="font-size:14px;color:red;">
                                Még nincsenek alkatrészek hozzáadva.
                                </span>
                                </td>
                            </tr>
                            <?php else: ?>
                        </table>
                        <table align="center" class="table-border" width="100%">
                            <tr>
                                <td style=" padding:20px; text-align:center;">
                                <table align="center" style="width:100%;text-align:center;" class="table-border parts-table">											
									<tr>
										<td class="pl10 epc-text row-odd p10 text-left" width="20%" style="background-color:#bb271a;color:#ffffff;">Cikkszám
										</td>
										<td class="pl10 epc-text row-odd p10 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
										</td>
										<td class="epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Mennyiség
										</td>
										<td class="pl10 epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Változás
										</td>
										<td class="row-odd" width="140" style="background-color:#bb271a;color:#ffffff;">
										</td>
										<td class="row-odd" width="140" style="background-color:#bb271a;color:#ffffff;">
										</td>
									</tr>
								</table>
                                <table align="center" style="width:100%;text-align:center;" class="table-border parts-list">
                        <?php foreach ($parts as $index => $part): ?>
                        <?php
                        $rowClass = ($index % 2 === 0)
                            ? 'row-even'
                            : 'row-odd';
                        ?>
									<tr data-part-row data-category="<?= htmlspecialchars((string)$part['category'], ENT_QUOTES, 'UTF-8') ?>">
										<td class="pl10 epc-text <?= $rowClass ?> p5 text-left" width="20%"><?= htmlspecialchars( $part['part_number'],  ENT_QUOTES, 'UTF-8' ) ?>
										</td>
										<td class="pl10 epc-text <?= $rowClass ?> p5 text-left"><?= htmlspecialchars( $part['name'],  ENT_QUOTES,  'UTF-8' ) ?>
										</td>
										<?php
$quantity = (float)$part['quantity'];

$quantityDisplay = rtrim(
    rtrim(number_format($quantity, 1, ',', ''), '0'),
    ','
);

$unit = (string)($part['unit'] ?? 'db');
?>
										<td class="epc-text <?= $rowClass ?> p5 text-center part-current-quantity" width="7%"><?= htmlspecialchars($quantityDisplay . ' ' . $unit, ENT_QUOTES, 'UTF-8') ?>
										</td>
										<td class="epc-text <?= $rowClass ?> p5 text-center" width="7%">
										<input type="text" inputmode="decimal" class="part-quantity-input" data-current="<?= (float)$part['quantity'] ?>" data-unit="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>" data-part-id="<?= (int)$part['id'] ?>" placeholder="<?= $part['quantity'] ?>" style="width:60px;" autocomplete="off">
										</td>
										<td class="<?= $rowClass ?> greybutton" width="140">
										<button type="button" class="part-save-button" disabled style="width:140px">Mentés</button>
										</td>
										<td class="<?= $rowClass ?>" width="140"><button style="width:140px" type="button" class="part-delete-button">Törlés</button>
										</td>
									</tr>
                    	<?php endforeach; ?>
								</table>
                                </td>
                            </tr>
                        </table>
                        <?php endif; ?>
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
