<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';

$page = $_GET['page'] ?? 'tortenet';

$backUrl = isset($configs[$page])
    ? '/epc/'
    : '/';

$allowed_pages = [
    'cn',
    'clr'
];

$from_cars = $_GET['from_cars'] ?? '';
?>
<html>
<title>RichCars - EPC</title>
<head>
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
						<?php if (isset($configs[$page])): ?>
						<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/epc/list.php';?>
						<?php else: ?>
						<table align="center" width="100%">
                    		<tr>
                       			<td style="padding:0px;text-align:center;">
                       			<span class="epc-title">EPC</span>
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
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
		</td>
	</tr>
</table>
</body>
</html>
