<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';

$brand = strtolower(trim($_GET['brand'] ?? ''));
$model = strtolower(trim($_GET['model'] ?? ''));
$series = strtolower(trim($_GET['series'] ?? ''));
$modelCode = strtolower(trim($_GET['model_code'] ?? ''));
$bodyCode = strtolower(trim($_GET['body_code'] ?? ''));
$trimCode = strtolower(trim($_GET['trim_code'] ?? ''));

$from_cars = $_GET['from_cars'] ?? '';



/*
 * EPC konfiguráció keresése
 */
$config = null;
$hasConfig = false;

$epcError = '';

if (isUser() && !isset($_GET['car'])) {
    $epcError = 'A kiválasztott autóhoz EPC nem érhető el.';
}

$userCar = null;
$carYear = 0;

if (isset($_GET['car'])) {

    $carId = filter_input(
        INPUT_GET,
        'car',
        FILTER_VALIDATE_INT
    );

    if (
        $carId === false ||
        $carId === null ||
        $carId <= 0
    ) {

        $epcError =
            'A kiválasztott autóhoz EPC nem érhető el.';

    } else {

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

        if ($userCar === null) {

            $epcError =
                'A kiválasztott autóhoz EPC nem érhető el.';

        } else {

            $carYear = (int)($userCar['production_year'] ?? 0);

            if ($carYear <= 0) {
                $epcError =
                    'A kiválasztott autóhoz EPC nem érhető el.';
            }
        }
    }
}


/*
 * EPC konfiguráció keresése.
 */
if (
    $epcError === '' &&
    $brand !== '' &&
    $model !== '' &&
    $series !== '' &&
    $modelCode !== '' &&
    $bodyCode !== '' &&
    $trimCode !== ''
) {

    foreach ($configs as $item) {

        if (
            strtolower((string)($item['brand'] ?? '')) === $brand &&
            strtolower((string)($item['model'] ?? '')) === $model &&
            strtolower((string)($item['series'] ?? '')) === $series &&
            strtolower((string)($item['model_code'] ?? '')) === $modelCode &&
            strtolower((string)($item['body_code'] ?? '')) === $bodyCode &&
            strtolower((string)($item['trim_code'] ?? '')) === $trimCode
        ) {

            /*
             * Ha konkrét autót választottunk,
             * az EPC-nek az autó évjáratára is
             * érvényesnek kell lennie.
             */
            if (
                $userCar !== null &&
                !in_array(
                    $carYear,
                    array_map('intval', $item['years'] ?? []),
                    true
                )
            ) {
                continue;
            }

            $config = $item;
            break;
        }
    }
}

$hasConfig = ($config !== null);

if (isset($_GET['car'])) {

    $carId = filter_input(
        INPUT_GET,
        'car',
        FILTER_VALIDATE_INT
    );

    $userCar = null;

    if (
        $carId === false ||
        $carId === null ||
        $carId <= 0
    ) {

        $epcError =
            'A kiválasztott autóhoz EPC nem érhető el.';

    } else {

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

        if ($userCar === null) {
            $epcError =
                'A kiválasztott autóhoz EPC nem érhető el.';
        }
    }
}

/*
 * Ha EPC paraméterek érkeztek,
 * de nincs hozzájuk megfelelő konfiguráció,
 * akkor hibát jelezünk.
 */
if (
    $epcError === '' &&
    (
        $brand !== '' ||
        $model !== '' ||
        $series !== '' ||
        $modelCode !== '' ||
        $bodyCode !== '' ||
        $trimCode !== ''
    ) &&
    !$hasConfig
) {
    $epcError =
        'A kiválasztott autóhoz EPC nem érhető el.';
}
/*
 * AJAX kérés esetén csak az EPC listát adjuk vissza.
 */
if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === '1' &&
    $hasConfig
) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/list.php';
    exit;
}

?>
<html>
<head>
	<title>RichCars - EPC</title>
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
						<a href="<?= htmlspecialchars($returnUrl) ?>" class="leftmenu-back">Vissza</a><br>
						</td>
						
					</tr>
				</table>
				</td>
			</tr>
			<tr>
				<td align="left">
				<table style="width:100%;padding-bottom:20px;min-height:250px;">
					<tr>
						<td class="textv-top">
						<?php if ($epcError === '' && $hasConfig): ?>
						<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/list.php';?>
						<?php else: ?>
                        <table align="left" width="100%" class="table-border">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title">EPC</span>
                       			</td>
                  			</tr>
                  		</table>
<?php if ($epcError !== ''): ?>
    <table align="center" width="100%">
        <tr>
            <td style="padding:0px;text-align:center;">
                <span class="epc-title5">
                    <?= htmlspecialchars($epcError) ?>
                </span>
            </td>
        </tr>
    </table>
<?php endif; ?>
<?php if (isAdmin()): ?>
						<table align="center" width="100%">

<?php foreach ($configs as $key => $config): ?>

<?php if ($key % 3 === 0): ?>
<tr>
<?php endif; ?>

<td style="width:33.33%;text-align:center;padding:20px;">
<a href="?brand=<?= urlencode($config['brand']) ?>&model=<?= urlencode($config['model']) ?>&series=<?= urlencode($config['series']) ?>&model_code=<?= urlencode($config['model_code']) ?>&body_code=<?= urlencode($config['body_code']) ?>&trim_code=<?= urlencode($config['trim_code']) ?>" style="text-decoration:none;">

<img src="<?= htmlspecialchars($config['preview']) ?>" width="100%"><br><br>

<span><?= htmlspecialchars($config['name']) ?></span>

</a>
</td>

<?php if (($key + 1) % 3 === 0): ?>
</tr>
<?php endif; ?>

<?php endforeach; ?>

<?php if (count($configs) % 3 !== 0): ?>
</tr>
<?php endif; ?>

</table>
<?php endif; ?>
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
<script>
document.addEventListener('click', function (event) {

    const link = event.target.closest('a[data-category]');

    if (!link) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const href = link.getAttribute('href');

    if (!href) {
        return;
    }

    /*
     * AJAX kérés URL-je.
     */
    let ajaxUrl = href;

    if (ajaxUrl.indexOf('?') !== -1) {
        ajaxUrl += '&ajax=1';
    } else {
        ajaxUrl += '?ajax=1';
    }

    ajaxUrl = '/epc/' + ajaxUrl.replace(/^\//, '');

    console.log('EPC AJAX URL:', ajaxUrl);

    fetch(ajaxUrl)

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'HTTP hiba: ' + response.status
                );
            }

            return response.text();

        })

        .then(function (html) {

            console.log('EPC AJAX SIKER');

            const oldList =
                document.getElementById('epc-list');

            if (!oldList) {
                throw new Error(
                    'Az #epc-list elem nem található.'
                );
            }

            /*
             * Csak az EPC listát cseréljük.
             */
            oldList.outerHTML = html;


            /*
             * A kiválasztott kategória gombjának
             * állapotát frissítjük.
             */
            document
                .querySelectorAll('a[data-category]')
                .forEach(function (button) {

                    if (
                        button.dataset.category ===
                        link.dataset.category
                    ) {

                        button.classList.remove(
                            'greybutton'
                        );

                    } else {

                        button.classList.add(
                            'greybutton'
                        );

                    }

                });


            /*
             * A böngésző címsorát frissítjük
             * oldalbetöltés nélkül.
             */
            history.pushState(
                {},
                '',
                href
            );

        })

        .catch(function (error) {

            console.error(
                'EPC AJAX HIBA:',
                error
            );

        });

});


/*
 * Böngésző Vissza / Előre gomb kezelése.
 */
window.addEventListener('popstate', function () {

    const currentUrl = new URL(
        window.location.href
    );

    /*
     * Az AJAX kéréshez hozzáadjuk
     * a technikai ajax=1 paramétert.
     */
    currentUrl.searchParams.set(
        'ajax',
        '1'
    );

    fetch(
        currentUrl.pathname +
        currentUrl.search
    )

        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'HTTP hiba: ' + response.status
                );
            }

            return response.text();

        })

        .then(function (html) {

            const oldList =
                document.getElementById('epc-list');

            if (!oldList) {
                return;
            }

            /*
             * Vissza / Előre esetén is
             * csak az EPC listát cseréljük.
             */
            oldList.outerHTML = html;

        })

        .catch(function (error) {

            console.error(
                'EPC HISTORY AJAX HIBA:',
                error
            );

        });

});
</script>
</body>
</html>
