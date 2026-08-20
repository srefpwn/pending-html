<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';


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
 * Van-e konkrét manual kiválasztva?
 */

$manualSelected =
    $type !== ''
    && $year !== false
    && $year !== null
    && $year > 0;


/*
 * Vissza URL
 */

$backUrl = '/';


?>
<html>
<title>RichCars - Manual</title>
<head>
<meta charset="UTF-8">
		<link href="/css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<table class="table-100-center">
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
						<?php if ($manualSelected): ?>

    <?php
    /*
     * A kiválasztott manual betöltése
     */

    require_once $_SERVER['DOCUMENT_ROOT'] . '/manual/list.php';
    ?>

<?php else: ?>
						<table align="center" width="100%">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title">Manual</span>
                       			</td>
                  			</tr>
                  		</table>
						<table align="center" width="100%">
    						<tr>
								<?php foreach ($configs as $key => $config): ?>
        						<td style="width:33.33%;text-align:center;padding:20px;">
								<a href="?page=<?= urlencode($key) ?>" style="text-decoration:none;">
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
		</td>
	</tr>
</table>
</body>
</html>
