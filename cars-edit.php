<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

/*
 * Admin jogosultság
 */

if (!isAdmin()) {
    http_response_code(403);
    exit('Nincs jogosultságod az oldal megtekintéséhez.');
}

/*
 * Azonosítók
 */

$userId = filter_input(
    INPUT_GET,
    'user_id',
    FILTER_VALIDATE_INT
);

$carId = filter_input(
    INPUT_GET,
    'car_id',
    FILTER_VALIDATE_INT
);

if (
    $userId === false ||
    $userId === null ||
    $userId < 1 ||
    $carId === false ||
    $carId === null ||
    $carId < 1
) {
    http_response_code(400);
    exit('Érvénytelen azonosító.');
}

/*
 * Összes felhasználói autó betöltése
 */

$carsData = loadUserCarsData();

$userKey = (string)$userId;

if (
    !isset($carsData[$userKey]) ||
    !is_array($carsData[$userKey])
) {
    http_response_code(404);
    exit('A felhasználó autói nem találhatók.');
}

/*
 * A konkrét autó megkeresése
 */

$car = null;

foreach ($carsData[$userKey] as $item) {

    if (
        isset($item['id']) &&
        (int)$item['id'] === $carId
    ) {
        $car = $item;
        break;
    }
}

if ($car === null) {
    http_response_code(404);
    exit('A kért autó nem található.');
}

/*
 * VIN konfiguráció
 */
 
 /*
 * Üzenetek
 */

$message = '';
$messageType = '';


/*
 * CSRF token
 */

if (empty($_SESSION['cars_csrf_token'])) {
    $_SESSION['cars_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['cars_csrf_token'];


/*
 * Mentés
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $csrfToken,
            (string)$_POST['csrf_token']
        )
    ) {
        http_response_code(403);
        exit('Érvénytelen CSRF token.');
    }

    $updatedCar = [
        'id' => $carId,
        'name' => trim((string)($_POST['name'] ?? '')),
        'vin' => normalizeVin((string)($_POST['vin'] ?? '')),
        'brand' => trim((string)($_POST['brand'] ?? '')),
        'model' => trim((string)($_POST['model'] ?? '')),
        'production_year' => trim((string)($_POST['production_year'] ?? '')),
        'series' => trim((string)($_POST['series'] ?? '')),
        'body' => trim((string)($_POST['body'] ?? '')),
        'engine' => trim((string)($_POST['engine'] ?? '')),
        'trim' => trim((string)($_POST['trim'] ?? '')),
        'color' => trim((string)($_POST['color'] ?? '')),
    ];

    if (
        $updatedCar['vin'] === ''
    ) {
        $message = 'A VIN megadása kötelező.';
        $messageType = 'error';

    } else {

        $saved = updateUserCar(
            $userId,
            $carId,
            $updatedCar
        );

       if ($saved) {

    $permissions = [
        'epc_enable' =>
            (string)($_POST['epc_enable'] ?? '0'),

        'manual_enable' =>
            (string)($_POST['manual_enable'] ?? '0'),

        'servicetips_enable' =>
            (string)($_POST['servicetips_enable'] ?? '0'),
    ];

    $permissionsSaved = saveVinPermissions(
        $updatedCar['vin'],
        $permissions
    );

    if ($permissionsSaved) {

        $message = 'Az autó adatai és jogosultságai sikeresen mentve.';
        $messageType = 'success';

        $car = $updatedCar;

    } else {

        $message = 'Az autó adatai mentve lettek, de a jogosultságok mentése sikertelen.';
        $messageType = 'error';

        $car = $updatedCar;
    }

} else {

    $message = 'Az autó adatainak mentése sikertelen.';
    $messageType = 'error';
}
    }
}

$carConfig = getCarConfig($car);

$epcEnabled =
    (string)($carConfig['epc_enable'] ?? '0') === '1';

$manualEnabled =
    (string)($carConfig['manual_enable'] ?? '0') === '1';

$servicetipsEnabled =
    (string)($carConfig['servicetips_enable'] ?? '0') === '1';

/*
 * Vissza URL
 */

$backUrl = '/cars/';



/*
 * Űrlap alapértékek
 */

$selectedBrand = $car['brand'] ?? '';

$brandModels =
    $car_catalog[$selectedBrand]['models'] ?? [];

$selectedModel = $car['model'] ?? '';

$modelConfig =
    $brandModels[$selectedModel] ?? null;


?>
<html>
<head>
    <title>RichCars - Autó hozzáadása</title>
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
                                                            <span class="epc-title">Autó Módosítása</span>
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
                                                        <td style="padding:20px;text-align:center;padding-bottom:40px;">
                                                    	<form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="series" id="seriesInput" value="">
														<table align="center" class="table-border" width="100%">
															<tr>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;">Adatok
																</td>
															</tr>
														</table>
                                                        <table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
                                                        <!-- Autó neve -->
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Autó neve:
																</td>
																<td width="50%" class="row-even p5">
																<input type="text" name="name" maxlength="50" value="<?= htmlspecialchars($car['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Alvázszám:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="text" name="vin" maxlength="30" placeholder="Alvázszám" value="<?= htmlspecialchars($car['vin'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Márka:
																</td>
																<td width="50%" class="row-even p5">
																<span class="select-wrapper2">
																<select name="brand" required>
    															<?php foreach ($car_catalog as $brandKey => $brandData): ?>
        															<option value="<?= htmlspecialchars($brandKey) ?>"
            														<?= $brandKey === $selectedBrand ? 'selected' : '' ?>>
            														<?= htmlspecialchars($brandData['name']) ?>
        															</option>
    															<?php endforeach; ?>
																</select>
                                                                </span>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Típus:
																</td>
																<td width="50%" class="row-odd p5">
																<span class="select-wrapper2">
																<select name="model" required>
																	<option value="">- Válasszon -</option>
    															<?php foreach ($brandModels as $modelKey => $modelData): ?>
        															<option value="<?= htmlspecialchars($modelKey) ?>"
            														<?= $modelKey === $selectedModel ? 'selected' : '' ?>>
            														<?= htmlspecialchars($modelData['name']) ?>
        															</option>
    															<?php endforeach; ?>
																</select>
                                                                </span>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Gyártási idő:
																</td>
																<td width="50%" class="row-even p5">
																<span class="select-wrapper2">
																<select name="production_year" required>
    																<option value="">- Válasszon -</option>
																</select>
                                                                </span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-odd p5">Kivitel:
																</td>
																<td width="50%" class="row-odd p5">
																<span class="select-wrapper2">
																<select name="body" required>
																	<option value="">- Válasszon -</option>
																<?php foreach (($modelConfig['options']['body'] ?? []) as $bodyKey => $bodyLabel): ?>
    																<option value="<?= htmlspecialchars($bodyKey) ?>">
        															<?= htmlspecialchars($bodyLabel) ?>
    																</option>
																<?php endforeach; ?>
																</select>
                                                                </span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-even p5">Motor:
																</td>
																<td width="50%" class="row-even p5">
																<span class="select-wrapper2">
																<select name="engine" required>
																	<option value="">- Válasszon -</option>
    															<?php foreach (($modelConfig['options']['engine'] ?? []) as $engineKey => $engineLabel): ?>
        															<option value="<?= htmlspecialchars($engineKey) ?>">
            														<?= htmlspecialchars($engineLabel) ?>
        															</option>
    															<?php endforeach; ?>
																</select>
																</span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-odd p5">Felszereltség:
																</td>
																<td width="50%" class="row-odd p5">
																<span class="select-wrapper2">
																<select name="trim" required>
																	<option value="">- Válasszon -</option>
    															<?php foreach (($modelConfig['options']['trim'] ?? []) as $trimKey => $trimLabel): ?>
        															<option value="<?= htmlspecialchars($trimKey) ?>">
            														<?= htmlspecialchars($trimLabel) ?>
        															</option>
    															<?php endforeach; ?>
																</select>
                                                                </span>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-even p5">Szín:
																</td>
																<td width="50%" class="row-even p5">
																<span class="select-wrapper2">
																<select name="color" required>
																	<option value="">- Válasszon -</option>
    															<?php foreach (($modelConfig['options']['color'] ?? []) as $colorCode => $colorName): ?>
        															<option value="<?= htmlspecialchars($colorCode) ?>">
            														<?= htmlspecialchars($colorName) ?> - <?= htmlspecialchars($colorCode) ?>
        															</option>
    															<?php endforeach; ?>
																</select>
                                                                </span>
																</td>
															</tr>
                                                        </table>
														<table align="center" class="table-border" width="100%">
															<tr>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;">Jogosultságok
																</td>
															</tr>
														</table>
														<table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
                                                        	<tr>
																<td width="50%" class="epc-text row-even p5">EPC:
																</td>
																<td width="50%" class="row-even p5">
																<select name="epc_enable">
            														<option value="1" <?= ($carConfig['epc_enable'] ?? '0') === '1' ? 'selected' : '' ?>>Engedélyezés</option>
            														<option value="0" <?= ($carConfig['epc_enable'] ?? '0') === '0' ? 'selected' : '' ?>>Tiltás</option>
        														</select>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Manual:
																</td>
																<td width="50%" class="row-odd p5">
																<select name="manual_enable">
            														<option value="1" <?= ($carConfig['manual_enable'] ?? '0') === '1' ? 'selected' : '' ?>>Engedélyezés</option>
           															<option value="0" <?= ($carConfig['manual_enable'] ?? '0') === '0' ? 'selected' : '' ?>>Tiltás</option>
        														</select>
																</td>
															</tr>
															<tr>
																<td width="50%" class="epc-text row-even p5">ServiceTips:
																</td>
																<td width="50%" class="row-even p5">
																<select name="servicetips_enable">
            														<option value="1" <?= ($carConfig['servicetips_enable'] ?? '0') === '1' ? 'selected' : '' ?>>Engedélyezés</option>
																	<option value="0" <?= ($carConfig['servicetips_enable'] ?? '0') === '0' ? 'selected' : '' ?>>Tiltás</option>
        														</select>
																</td>
															</tr>
														</table>
                                                        <table class="table-border text-center">
                                                        <!-- Gombok -->
                                                            <tr>
                                                                <td></td>
                                                                <td style="padding-top:10px;">
                                                                <button type="submit">beállítások mentése
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
<script>
const carCatalog = <?= json_encode(
    $car_catalog,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) ?>;

const brandSelect = document.querySelector('select[name="brand"]');
const modelSelect = document.querySelector('select[name="model"]');
const yearSelect = document.querySelector('select[name="production_year"]');
const bodySelect = document.querySelector('select[name="body"]');
const engineSelect = document.querySelector('select[name="engine"]');
const trimSelect = document.querySelector('select[name="trim"]');
const colorSelect = document.querySelector('select[name="color"]');
const vinInput = document.querySelector('input[name="vin"]');
let vinLockedFields = new Set();

const seriesInput = document.querySelector('input[name="series"]');

let activeSeries = '';
let yearSeriesMap = {};

function populateSelect(select, options) {
    select.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '- Válasszon -';
    select.appendChild(placeholder);

    Object.entries(options || {}).forEach(([value, label]) => {
        const option = document.createElement('option');

        option.value = value;
        
        if (select === colorSelect) {
            option.textContent = `${label} - ${value}`;
        } else {
            option.textContent = label;
        }

        select.appendChild(option);
    });
}
function populateYearSelect(seriesConfig) {
    yearSelect.innerHTML = '';
    yearSeriesMap = {};
    activeSeries = '';
    seriesInput.value = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '- Válasszon -';
    yearSelect.appendChild(placeholder);

    Object.entries(seriesConfig || {}).forEach(([seriesKey, seriesData]) => {

        const years = seriesData?.years || {};

        if (Object.keys(years).length === 0) {
            return;
        }

        const group = document.createElement('optgroup');
        group.label = seriesData.name || seriesKey;

        Object.entries(years).forEach(([yearValue, yearLabel]) => {

            const option = document.createElement('option');

            option.value = yearValue;
            option.textContent = yearLabel;

            yearSeriesMap[yearValue] = seriesKey;

            group.appendChild(option);
        });

        yearSelect.appendChild(group);
    });
}
yearSelect.addEventListener('change', function () {

    const year = this.value;

    activeSeries = yearSeriesMap[year] || '';
    seriesInput.value = activeSeries;

    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig =
        carCatalog[brand]?.models?.[model];

    const seriesConfig =
        modelConfig?.series?.[activeSeries];

    if (!seriesConfig) {
        populateSelect(bodySelect, {});
        populateSelect(engineSelect, {});
        populateSelect(trimSelect, {});
        populateSelect(colorSelect, {});
        return;
    }

    populateSelect(
        bodySelect,
        seriesConfig.options?.body
    );

    populateSelect(
        engineSelect,
        seriesConfig.options?.engine
    );

    populateSelect(
        trimSelect,
        seriesConfig.options?.trim
    );

    populateSelect(
        colorSelect,
        seriesConfig.options?.color
    );
});

function loadModelConfig(values = {}) {
    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig = carCatalog[brand]?.models?.[model];

    if (!modelConfig) {
        populateYearSelect({});
        populateSelect(bodySelect, {});
        populateSelect(engineSelect, {});
        populateSelect(trimSelect, {});
        populateSelect(colorSelect, {});
        return;
    }

    /*
     * Évjáratok szériák szerint
     */
    populateYearSelect(modelConfig.series || {});

    /*
     * Típus kiválasztásakor nincs automatikus évjárat.
     * A többi mező sem töltődik ki addig,
     * amíg nincs kiválasztva évjárat.
     */
    populateSelect(bodySelect, {});
    populateSelect(engineSelect, {});
    populateSelect(trimSelect, {});
    populateSelect(colorSelect, {});

    if (values.production_year) {
        yearSelect.value = values.production_year;

        activeSeries =
            yearSeriesMap[values.production_year] || '';

        seriesInput.value = activeSeries;

        const seriesConfig =
            modelConfig.series?.[activeSeries];

        if (seriesConfig) {
            populateSelect(
                bodySelect,
                seriesConfig.options?.body
            );

            populateSelect(
                engineSelect,
                seriesConfig.options?.engine
            );

            populateSelect(
                trimSelect,
                seriesConfig.options?.trim
            );

            populateSelect(
                colorSelect,
                seriesConfig.options?.color
            );
        }
    }
}
function lockSelect(select, value) {
    if (!select) {
        return;
    }

    Array.from(select.options).forEach(option => {
        option.disabled = option.value !== value;
    });

    select.value = value;
}

function unlockSelect(select) {
    if (!select) {
        return;
    }

    Array.from(select.options).forEach(option => {
        option.disabled = false;
    });
}
function applyVinLocks(vinValues) {
    vinLockedFields = new Set();

    const lockedFields = [
        'production_year',
        'body',
        'engine'
    ];

    lockedFields.forEach(field => {
        const value = vinValues[field];

        if (!value) {
            return;
        }

        let select = null;

        switch (field) {
            case 'production_year':
                select = yearSelect;
                break;

            case 'body':
                select = bodySelect;
                break;

            case 'engine':
                select = engineSelect;
                break;
        }

        if (!select) {
            return;
        }

        lockSelect(select, value);
        vinLockedFields.add(field);
    });
}
function loadBrandModels(brand, selectedModel = '', lockModel = false) {
    const models = carCatalog[brand]?.models || {};

    modelSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '- Válasszon -';

    if (lockModel) {
        placeholder.disabled = true;
    }

    modelSelect.appendChild(placeholder);

    Object.entries(models).forEach(([modelKey, modelData]) => {
        const option = document.createElement('option');

        option.value = modelKey;
        option.textContent = modelData.name;

        if (modelKey === selectedModel) {
            option.selected = true;
        }

        if (lockModel && modelKey !== selectedModel) {
            option.disabled = true;
        }

        modelSelect.appendChild(option);
    });
}
brandSelect.addEventListener('change', function () {
    const brand = this.value;
    const models = carCatalog[brand]?.models || {};

    modelSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '- Válasszon -';
    modelSelect.appendChild(placeholder);

    Object.entries(models).forEach(([modelKey, modelData]) => {
        const option = document.createElement('option');

        option.value = modelKey;
        option.textContent = modelData.name;

        modelSelect.appendChild(option);
    });

    populateSelect(yearSelect, {});
    populateSelect(bodySelect, {});
    populateSelect(engineSelect, {});
    populateSelect(trimSelect, {});
    populateSelect(colorSelect, {});
});

modelSelect.addEventListener('change', function () {
    loadModelConfig();
});

function loadModelConfig(values = {}) {
    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig = carCatalog[brand]?.models?.[model];

    if (!modelConfig) {
        populateYearSelect({});
        populateSelect(bodySelect, {});
        populateSelect(engineSelect, {});
        populateSelect(trimSelect, {});
        populateSelect(colorSelect, {});
        return;
    }

    /*
     * Évjáratok szériák szerint
     */
    populateYearSelect(modelConfig.series || {});

    /*
     * Típus kiválasztásakor még nincs évjárat,
     * ezért a többi mező üres marad.
     */
    populateSelect(bodySelect, {});
    populateSelect(engineSelect, {});
    populateSelect(trimSelect, {});
    populateSelect(colorSelect, {});

    activeSeries = '';
    seriesInput.value = '';

    /*
     * Ha már van konkrét évjárat,
     * meghatározzuk hozzá a szériát.
     */
    if (values.production_year) {

        yearSelect.value = values.production_year;

        activeSeries =
            yearSeriesMap[values.production_year] || '';

        seriesInput.value = activeSeries;

        const seriesConfig =
            modelConfig.series?.[activeSeries];

        if (seriesConfig) {

            populateSelect(
                bodySelect,
                seriesConfig.options?.body
            );

            populateSelect(
                engineSelect,
                seriesConfig.options?.engine
            );

            populateSelect(
                trimSelect,
                seriesConfig.options?.trim
            );

            populateSelect(
                colorSelect,
                seriesConfig.options?.color
            );
        }
    }

    if (values.body) {
        bodySelect.value = values.body;
    }

    if (values.engine) {
        engineSelect.value = values.engine;
    }

    if (values.trim) {
        trimSelect.value = values.trim;
    }

    if (values.color) {
        colorSelect.value = values.color;
    }
}
loadModelConfig({
    production_year: <?= json_encode($car['production_year'] ?? '') ?>,
    body: <?= json_encode($car['body'] ?? '') ?>,
    engine: <?= json_encode($car['engine'] ?? '') ?>,
    trim: <?= json_encode($car['trim'] ?? '') ?>,
    color: <?= json_encode($car['color'] ?? '') ?>
});
function findVinModel(vin) {
    for (const [brandKey, brandConfig] of Object.entries(carCatalog)) {

        for (const [modelKey, modelConfig] of Object.entries(brandConfig.models || {})) {

            for (const [seriesKey, seriesConfig] of Object.entries(modelConfig.series || {})) {

                const rules = seriesConfig.vin?.rules || [];

                for (const rule of rules) {

                    if (rule.model_code !== true) {
                        continue;
                    }

                    const position = rule.position - 1;

                    const value = vin.substring(
                        position,
                        position + rule.length
                    );

                    if (rule.values?.[value] !== undefined) {
                        return {
                            brand: brandKey,
                            model: modelKey,
                            series: seriesKey
                        };
                    }
                }
            }
        }
    }

    return null;
}
function decodeVin(vin, seriesKey = '') {
    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig =
        carCatalog[brand]?.models?.[model];

    const seriesConfig =
        modelConfig?.series?.[seriesKey];

    if (!seriesConfig?.vin?.rules) {
        return {};
    }

    const vinValues = {};

    seriesConfig.vin.rules.forEach(rule => {

        const position = rule.position - 1;

        const value = vin.substring(
            position,
            position + rule.length
        );

        let result;

        if (rule.engine_values) {

            const engine = vinValues.engine;

            result =
                rule.engine_values?.[engine]?.[value];

        } else {

            result = rule.values?.[value];
        }

        if (result !== undefined) {
            vinValues[rule.target] = result;
        }
    });

    return vinValues;
}
function processVin(vin) {
    const modelInfo = findVinModel(vin);

    if (!modelInfo) {
        return null;
    }

    brandSelect.value = modelInfo.brand;

    loadBrandModels(
        modelInfo.brand,
        modelInfo.model,
        true
    );

   const vinValues = decodeVin(vin, modelInfo.series);

    loadModelConfig(vinValues);

    applyVinLocks(vinValues);

    return {
        ...modelInfo,
        values: vinValues
    };
}
function clearVinLocks() {
    unlockSelect(yearSelect);
    unlockSelect(bodySelect);
    unlockSelect(engineSelect);

    vinLockedFields.clear();

    loadBrandModels(brandSelect.value, modelSelect.value);
}
vinInput.addEventListener('input', function () {
    const vin = this.value.trim().toUpperCase();

    if (vin.length < 17) {
        clearVinLocks();
        return;
    }

    if (vin.length === 17) {
        const result = processVin(vin);

        console.log('VIN eredmény:', result);
    }
});

</script>
</body>
</html>
