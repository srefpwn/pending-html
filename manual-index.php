<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/cars/functions.php';
require_once __DIR__ . '/functions.php';


/*
 * Manual típusa
 *
 * Példa:
 * /manual/?type=cn1&year=2006
 */

$type = strtolower(
    trim((string)($_GET['type'] ?? ''))
);


/*
 * Évjárat
 */

$year = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT
);


/*
 * Manual könyv típusa
 */

$book = strtolower(
    trim((string)($_GET['book'] ?? 'manual'))
);

if (!in_array($book, ['manual', 'body'], true)) {
    $book = 'manual';
}


/*
 * Van-e konkrét manual kiválasztva?
 */

$manualSelected =
    $type !== ''
    && $year !== false
    && $year !== null
    && $year > 0;


/*
 * Manual ellenőrzése
 */

$manualError = '';

if ($manualSelected) {

    $listaFile = getManualListFile(
        $type,
        $year,
        $book
    );

    if (!is_file($listaFile)) {
        $manualError =
            'A kiválasztott típushoz és évjárathoz nem található szerviz kézikönyv.';
    }
}


/*
 * Vissza URL
 */

$backUrl = '/';

?>
<html>
<head>
	<title>RichCars - Szerviz kézikönyv</title>
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
				<table style="width:100%;">
					<tr>
						<td>
						<?php if ($manualSelected && $manualError === ''): ?>
    					<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/list.php'; ?>
						<?php else: ?>
                        <table align="left" width="100%" class="table-border">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<?php if ($manualError !== ''): ?>
                       			<span class="epc-title"><?= htmlspecialchars($manualError) ?></span>
                       			<?php endif; ?>
                       			</td>
                  			</tr>
                  			<tr>
                       			<td style="height:220px;">
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
<script>
document.addEventListener('click', function (event) {

    /*
     * -------------------------------------------------
     * FELSŐ TÉMA / KÖNYV GOMBOK
     * -------------------------------------------------
     */
    const bookLink = event.target.closest(
        '#manual-book a[data-book]'
    );

    if (bookLink) {

        event.preventDefault();
        event.stopImmediatePropagation();

        const href = bookLink.getAttribute('href');

        if (!href) {
            return;
        }

        let ajaxUrl = href;

        if (ajaxUrl.indexOf('?') !== -1) {
            ajaxUrl += '&ajax=1';
        } else {
            ajaxUrl += '?ajax=1';
        }

        ajaxUrl = '/manual/' + ajaxUrl.replace(/^\//, '');

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

                /*
                 * A teljes AJAX válaszból
                 * létrehozunk egy ideiglenes DOM-ot.
                 */
                const parser = new DOMParser();

                const doc = parser.parseFromString(
                    html,
                    'text/html'
                );

                /*
                 * Megkeressük az új három blokkot.
                 */
                const newBook =
                    doc.getElementById('manual-book');

                const newCategory =
                    doc.getElementById('manual-category');

                const newList =
                    doc.getElementById('manual-list');

                /*
                 * Ellenőrizzük, hogy mindhárom
                 * elem megérkezett-e.
                 */
                if (
                    !newBook ||
                    !newCategory ||
                    !newList
                ) {
                    throw new Error(
                        'A Manual AJAX válaszban nem található mindhárom szükséges elem.'
                    );
                }

                /*
                 * A jelenlegi blokkokat lecseréljük
                 * az új szerveroldali változatokra.
                 */
                const oldBook =
                    document.getElementById('manual-book');

                const oldCategory =
                    document.getElementById('manual-category');

                const oldList =
                    document.getElementById('manual-list');

                if (!oldBook || !oldCategory || !oldList) {
                    throw new Error(
                        'A jelenlegi Manual elemek nem találhatók.'
                    );
                }

                oldBook.outerHTML =
                    newBook.outerHTML;

                oldCategory.outerHTML =
                    newCategory.outerHTML;

                oldList.outerHTML =
                    newList.outerHTML;

                /*
                 * A böngésző URL-jét frissítjük,
                 * de ajax=1 nélkül.
                 */
                history.pushState(
                    {},
                    '',
                    href
                );

            })

            .catch(function (error) {

                console.error(
                    'MANUAL BOOK AJAX HIBA:',
                    error
                );

            });

        return;
    }


    /*
     * -------------------------------------------------
     * BAL OLDALI KATEGÓRIA GOMBOK
     * -------------------------------------------------
     */
    const categoryLink = event.target.closest(
        '#manual-category a[data-category]'
    );

    if (categoryLink) {

        event.preventDefault();
        event.stopImmediatePropagation();

        const href = categoryLink.getAttribute('href');

        if (!href) {
            return;
        }

        let ajaxUrl = href;

        if (ajaxUrl.indexOf('?') !== -1) {
            ajaxUrl += '&ajax=1';
        } else {
            ajaxUrl += '?ajax=1';
        }

        ajaxUrl = '/manual/' + ajaxUrl.replace(/^\//, '');

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

                const parser = new DOMParser();

                const doc = parser.parseFromString(
                    html,
                    'text/html'
                );

                /*
                 * Kategóriaváltásnál csak a
                 * jobb oldali listát kérjük le.
                 */
                const newList =
                    doc.getElementById('manual-list');

                if (!newList) {
                    throw new Error(
                        'A Manual AJAX válaszban nem található a #manual-list elem.'
                    );
                }

                const oldList =
                    document.getElementById('manual-list');

                if (!oldList) {
                    throw new Error(
                        'A jelenlegi #manual-list elem nem található.'
                    );
                }

                /*
                 * Csak a jobb oldali listát cseréljük.
                 */
                oldList.outerHTML =
                    newList.outerHTML;

                /*
                 * A bal oldali kategóriagombok
                 * aktív állapotát is frissítjük.
                 */
                document
                    .querySelectorAll(
                        '#manual-category a[data-category]'
                    )
                    .forEach(function (button) {

                        if (
                            button.dataset.category ===
                            categoryLink.dataset.category
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
                 * A böngésző URL-jét frissítjük
                 * ajax=1 nélkül.
                 */
                history.pushState(
                    {},
                    '',
                    href
                );

            })

            .catch(function (error) {

                console.error(
                    'MANUAL CATEGORY AJAX HIBA:',
                    error
                );

            });

        return;
    }

});


/*
 * -----------------------------------------------------
 * BÖNGÉSZŐ VISSZA / ELŐRE
 * -----------------------------------------------------
 */
window.addEventListener('popstate', function () {

    const currentUrl =
        new URL(window.location.href);

    /*
     * Csak az AJAX kéréshez adjuk hozzá
     * a technikai paramétert.
     */
    currentUrl.searchParams.set(
        'ajax',
        '1'
    );

    const ajaxUrl =
        currentUrl.pathname +
        currentUrl.search;

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

            const parser =
                new DOMParser();

            const doc =
                parser.parseFromString(
                    html,
                    'text/html'
                );

            const newBook =
                doc.getElementById('manual-book');

            const newCategory =
                doc.getElementById('manual-category');

            const newList =
                doc.getElementById('manual-list');

            if (
                !newBook ||
                !newCategory ||
                !newList
            ) {
                throw new Error(
                    'A Manual history AJAX válaszban nem található minden szükséges elem.'
                );
            }

            const oldBook =
                document.getElementById('manual-book');

            const oldCategory =
                document.getElementById('manual-category');

            const oldList =
                document.getElementById('manual-list');

            if (!oldBook || !oldCategory || !oldList) {
                return;
            }

            /*
             * Vissza / Előre esetén mindhárom
             * blokkot a szerver aktuális állapota
             * szerint állítjuk vissza.
             */
            oldBook.outerHTML =
                newBook.outerHTML;

            oldCategory.outerHTML =
                newCategory.outerHTML;

            oldList.outerHTML =
                newList.outerHTML;

        })

        .catch(function (error) {

            console.error(
                'MANUAL HISTORY AJAX HIBA:',
                error
            );

        });

});
</script>
</body>
</html>
