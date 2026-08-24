<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once __DIR__ . '/functions.php';


// Vissza URL
$backUrl = '/cars/';


// Üzenetek
$message = '';
$messageType = '';


// CSRF token
if (empty($_SESSION['cars_csrf_token'])) {
    $_SESSION['cars_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['cars_csrf_token'];


// POST kezelés
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF ellenőrzés
     */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['cars_csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        $_SESSION['cars_message'] = 'Érvénytelen kérés.';
        $_SESSION['cars_message_type'] = 'error';

    } else {

        /*
         * Adatok
         */
$vin = $_POST['vin'] ?? '';
$name = $_POST['name'] ?? '';

$brand = $_POST['brand'] ?? '';
$model = $_POST['model'] ?? '';

$productionYear = $_POST['production_year'] ?? '';
$body = $_POST['body'] ?? '';
$engine = $_POST['engine'] ?? '';
$trim = $_POST['trim'] ?? '';
$color = $_POST['color'] ?? '';


        /*
         * Autó hozzáadása
         */
        $result = addUserCar(
    $vin,
    $name,
    $brand,
    $model,
    $productionYear,
    $body,
    $engine,
    $trim,
    $color
);


        /*
         * Üzenet mentése sessionbe
         */
$_SESSION['cars_message'] = $result['message'];
$_SESSION['cars_message_type'] =
    $result['success'] ? 'success' : 'error';

if ($result['success']) {
    header('Location: /cars/');
} else {
    header('Location: /cars/add.php');
}

exit;
    }
}


// Sessionből érkező üzenet
$message = $_SESSION['cars_message'] ?? '';
$messageType = $_SESSION['cars_message_type'] ?? '';


// Üzenet egyszeri felhasználása
unset(
    $_SESSION['cars_message'],
    $_SESSION['cars_message_type']
);
$selectedBrand = 'honda';

$brandModels = $car_catalog[$selectedBrand]['models'] ?? [];
$selectedModel = '';

$modelConfig = $brandModels[$selectedModel] ?? null;
?>
<html>
<head>
    <title>RichCars - Autó hozzáadása</title>
    <meta charset="UTF-8">
    <link href="/css.css" rel="stylesheet" type="text/css" />
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
                                                            <span class="epc-title">Autó hozzáadása</span>
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
                                                        <td style="padding:20px;text-align:center;">
                                                    	<form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
														<table align="center" class="table-border" width="100%">
															<tr>
																<td style="background-color:#bb271a;color:#ffffff;width:400px;text-align:center;" class="p10">Adatok
																</td>
															</tr>
														</table>
                                                        <table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
                                                        <!-- Autó neve -->
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Autó neve:
																</td>
																<td width="50%" class="row-even p5">
																<input type="text" name="name" maxlength="50">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Alvázszám:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="text" name="vin" maxlength="30" placeholder="Alvázszám" required>
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
																<?php foreach (($modelConfig['years'] ?? []) as $yearValue => $yearLabel): ?>
                                                               		<option value="<?= htmlspecialchars($yearValue) ?>">
   																	<?= htmlspecialchars($yearLabel) ?>
																	</option>
            													<?php endforeach; ?>
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
                                                        <table class="table-border text-center">
                                                        <!-- Gombok -->
                                                            <tr>
                                                                <td></td>
                                                                <td style="padding-top:10px;">
                                                                <button type="submit">Autó hozzáadása
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

function populateSelect(select, options) {
    select.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '- Válasszon -';
    select.appendChild(placeholder);

    Object.entries(options || {}).forEach(([value, label]) => {
        const option = document.createElement('option');

        option.value = value;
        option.textContent = label;

        select.appendChild(option);
    });
}

function loadModelConfig() {
    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig = carCatalog[brand]?.models?.[model];

    if (!modelConfig) {
        populateSelect(yearSelect, {});
        populateSelect(bodySelect, {});
        populateSelect(engineSelect, {});
        populateSelect(trimSelect, {});
        populateSelect(colorSelect, {});
        return;
    }

    populateSelect(yearSelect, modelConfig.years);
    populateSelect(bodySelect, modelConfig.options?.body);
    populateSelect(engineSelect, modelConfig.options?.engine);
    populateSelect(trimSelect, modelConfig.options?.trim);
    populateSelect(colorSelect, modelConfig.options?.color);
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
        populateSelect(yearSelect, {});
        populateSelect(bodySelect, {});
        populateSelect(engineSelect, {});
        populateSelect(trimSelect, {});
        populateSelect(colorSelect, {});
        return;
    }

    populateSelect(yearSelect, modelConfig.years);
    populateSelect(bodySelect, modelConfig.options?.body);
    populateSelect(engineSelect, modelConfig.options?.engine);
    populateSelect(trimSelect, modelConfig.options?.trim);
    populateSelect(colorSelect, modelConfig.options?.color);

    if (values.production_year) {
        yearSelect.value = values.production_year;
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
loadModelConfig();
function findVinModel(vin) {
    for (const [brandKey, brandConfig] of Object.entries(carCatalog)) {

        for (const [modelKey, modelConfig] of Object.entries(brandConfig.models || {})) {

            const rules = modelConfig.vin?.rules || [];

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
                        model: modelKey
                    };
                }
            }
        }
    }

    return null;
}
function decodeVin(vin) {
    const brand = brandSelect.value;
    const model = modelSelect.value;

    const modelConfig = carCatalog[brand]?.models?.[model];

    if (!modelConfig?.vin?.rules) {
        return {};
    }

    const vinValues = {};

    modelConfig.vin.rules.forEach(rule => {
        const position = rule.position - 1;
        const value = vin.substring(position, position + rule.length);

        const result = rule.values?.[value];

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

    const vinValues = decodeVin(vin);

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
