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

    const validInteger = /^[0-9]+$/.test(value);
    const newQuantity = Number(value);

    const canSave =
        validInteger &&
        Number.isSafeInteger(newQuantity) &&
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

    if (!/^[0-9]+$/.test(quantityText)) {
        return;
    }

    const quantity = Number(quantityText);
    const currentQuantity = Number(input.dataset.current);

    if (
        !Number.isSafeInteger(quantity) ||
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

        quantityCell.textContent = String(result.quantity);
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
        const validInteger = /^[0-9]+$/.test(value);
        const newQuantity = Number(value);

        const canSave =
            validInteger &&
            Number.isSafeInteger(newQuantity) &&
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

        const tbody = document.querySelector('.parts-table tbody');

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
                                <table align="center" style="width:100%;text-align:center;" class="table-border">											
									<tr>
										<td class="pl10 epc-text row-odd p10 text-left" width="20%" style="background-color:#bb271a;color:#ffffff;">Cikkszám
										</td>
										<td class="pl10 epc-text row-odd p10 text-left" style="background-color:#bb271a;color:#ffffff;">Megnevezés
										</td>
										<td class="epc-text row-odd p10 text-center" width="7%" style="background-color:#bb271a;color:#ffffff;">Darab
										</td>
									</tr>
								</table>
                                <table align="center" style="width:100%;text-align:center;" class="table-border">
									<tr data-part-row>
										<td class="pl10 epc-text row-even p5 text-left" width="20%">
										<input type="text">
										</td>
										<td class="pl10 epc-text row-even p5 text-left">
										<input type="text">
										</td>
										<td class="epc-text row-even p5 text-center part-current-quantity" width="7%">
										<input type="text">
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
<a href=""><button type="submit">Mentés</button></a>
										</td>
									</tr>
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
