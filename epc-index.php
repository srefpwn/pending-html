<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

$brand = strtolower(trim($_GET['brand'] ?? ''));
$model = strtolower(trim($_GET['model'] ?? ''));
$series = strtolower(trim($_GET['series'] ?? ''));
$modelCode = strtolower(trim($_GET['model_code'] ?? ''));

$from_cars = $_GET['from_cars'] ?? '';

/*
 * EPC konfiguráció keresése
 */
$config = null;

if (
    $brand !== '' &&
    $model !== '' &&
    $series !== '' &&
    $modelCode !== ''
) {
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
}

$hasConfig = ($config !== null);

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
				<table style="width:100%;padding-bottom:20px;">
					<tr>
						<td>
						<?php if ($hasConfig): ?>
						<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/list.php';?>
						<?php else: ?>
                        <table align="left" width="100%" class="table-border">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title">EPC</span>
                       			</td>
                  			</tr>
                  		</table>
                  		<?php if (isset($_GET['car'])): ?>
                  		<table align="center" width="100%">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title" style="color:red;">Ehhez az autóhoz nincs EPC, válasszon az elérhető típusok közül.</span>
                       			</td>
                  			</tr>
                  		</table>
                  		<?php endif; ?>
						<table align="center" width="100%">
    						<tr>
								<?php foreach ($configs as $key => $config): ?>
        						<td style="width:33.33%;text-align:center;padding:20px;">
								<a href="?brand=<?= urlencode($config['brand']) ?>&model=<?= urlencode($config['model']) ?>&series=<?= urlencode($config['series']) ?>&model_code=<?= urlencode($config['model_code']) ?>" style="text-decoration:none;">
								<img src="<?= htmlspecialchars($config['preview']) ?>" width="100%"><br><br>
								<span><?= htmlspecialchars($config['name']) ?></span>
           						</a>
        						</td>
						<?php endforeach; ?>
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
