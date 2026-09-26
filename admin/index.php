<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/navigation.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';

if (!isAdmin()) {
    http_response_code(403);
    exit('Hozzáférés megtagadva.');
}

$users = loadUsers();


// Vissza URL
$backUrl = '/';


// Üzenetek
$message = '';
$messageType = '';


// CSRF token
if (empty($_SESSION['cars_csrf_token'])) {
    $_SESSION['cars_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['cars_csrf_token'];


?>
<html>
<head>
	<title>RichCars - Admin</title>
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
                                <!--
                                 * Autóim fő tartalom
                                 -->
                                <table align="left" width="100%" class="table-border">
                                    <tr>
                                        <td class="textv-top">
                                            <table align="left" class="table-border" width="100%">
                                                <!--
                                                 * Cím
                                                 -->
                                                <tr>
                                                    <td style="padding:0px;text-align:center;">
                                                        <span class="epc-title">Admin</span>
                                                    </td>
                                                </tr>
                                                <tr>
													<td style="padding:20px;text-align:center;">
													<table class="table-border text-center">
														<tr>
															<td>
															<a href="/cars/add.php"><button type="submit">Felhasználó hozzáadása</button></a>
															</td>
														</tr>
													</table>
													</td>
												</tr>
                                                <!--
                                                 * Üzenet
                                                 -->
                                                <?php if ($message !== ''): ?>
                                                <tr>
                                                    <td style="padding:20px;text-align:center;">
                                                        <?php if ($messageType === 'success'): ?>
                                                            <span style="font-size:14px;color:green;">
                                                                <?= htmlspecialchars($message) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span style="font-size:14px;color:red;">
                                                                <?= htmlspecialchars($message) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <td class="textv-top">
                                                        <table class="menutable" width="100%" style="padding-bottom:20px;">
                                                            <?php foreach ($users as $user): ?>
                                                            <tr>
                                                                <td style="padding:0px;background-color:#cccccc;">
                                                                    <table width="100%" class="text-center table-border">
                                                                        <tr>
                                                                            <td style="text-align:center; padding:20px;">
                                                                            <table class="table-border" style="text-align:center;">
                                                                            	<tr>
                                                                            		<td style="padding:10px;background-color:#444444;">
                                                                            		<span class="epc-title3"><?= htmlspecialchars($user['name']) ?></span>
                                                                    				</td>
                                                                           		</tr>
                                                                           	</table>
                                                                            </td>
                                                                            <td style="padding:20px; height:100%;">
                                                                            <table class="table-border text-right textv-top" style="height:100%;">
                                                                            	<tr>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button type="button" style="width:140px" onclick="toggleUserCars(<?= (int)$user['id'] ?>)">Autói</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Szerkesztés</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Törlés</button>
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
                                                                    <table width="100%" class="text-center table-border">
                                                                        <tr>
																			<td style="padding-top:20px;text-align:center;background-color:#aaaaaa;">
																			<table class="table-border text-center">
																				<tr>
																					<td>
																					<a href="/cars/add.php"><button type="submit">Autó hozzáadása</button></a>
																					</td>
																				</tr>
																			</table>
																			</td>
																		</tr>
                                                                    </table>
                                                                    <table width="100%" class="text-center table-border">
                                                                        <tr>
                                                                            <td style="width:300px;text-align:center; padding:20px;background-color:#aaaaaa;">
                                                                            <table class="table-border" style="text-align:center;">
                                                                            	<tr>
                                                                            		<td style="padding:10px;">
                                                                            		<span class="epc-text2"><b>Név: </b></span>
                                                                    				</td>
                                                                            		<td style="padding:10px;">
                                                                            		<span class="epc-text2">KIK</span>
                                                                    				</td>
                                                                           		</tr>
                                                                           	</table>
                                                                            </td>
                                                                            <td class="textv-center" style="padding-left:0px; padding-top:20px;padding-bottom:20px;background-color:#aaaaaa;">
                                                                            <table class="table-border" width="100%">
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;width:15%;">
                                                                            		<span class="epc-text2"><b>Alvázszám: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:left;">
                                                                            		<span class="epc-text2">SHSRE5790HU002409</span>
                                                                            		</td>
                                                                            	</tr>
                                                                           	</table>
                                                                            </td>
                                                                            <td style=" padding:20px; height:100%;background-color:#aaaaaa;">
                                                                            <table class="table-border text-right textv-top" style="height:100%;">
                                                                            	<tr>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Szervizkönyv</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Szerkesztés</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Törlés</button>
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
                                                                    <table width="100%" class="text-center table-border">
                                                                        <tr>
                                                                            <td style="width:300px;text-align:center; padding:20px;background-color:#aaaaaa;">
                                                                            <table class="table-border" style="text-align:center;">
                                                                            	<tr>
                                                                            		<td style="padding:10px;">
                                                                            		<span class="epc-text2"><b>Név: </b></span>
                                                                    				</td>
                                                                    				<td style="padding:10px;">
                                                                            		<span class="epc-text2">CRV</span>
                                                                    				</td>
                                                                           		</tr>
                                                                           	</table>
                                                                            </td>
                                                                            <td class="textv-center" style="padding-left:0px; padding-top:20px;padding-bottom:20px;background-color:#aaaaaa;">
                                                                            <table class="table-border" width="100%">
                                                                            	<tr>
                                                                            		<td style="height:auto;padding:10px;width:15%;">
                                                                            		<span class="epc-text2"><b>Alvázszám: </b></span>
                                                                            		</td>
                                                                            		<td style="height:auto;padding:10px;text-align:left;">
                                                                            		<span class="epc-text2">SHSRE5790HU002409</span>
                                                                            		</td>
                                                                            	</tr>
                                                                           	</table>
                                                                            </td>
                                                                            <td style=" padding:20px; height:100%;background-color:#aaaaaa;">
                                                                            <table class="table-border text-right textv-top" style="height:100%;">
                                                                            	<tr>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Szervizkönyv</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Szerkesztés</button>
																							</form>
																							</td>
																						</tr>
																					</table>
                                                                            		</td>
                                                                            		<td class="textv-bottom" style="vertical-align:bottom;">
                                                                            		<table class="table-border">
                                                                            			<tr>
                                                                            				<td>
                                                                            				<form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd ezt az autót?');">
																							<input type="hidden" name="action" value="delete">
																							<input type="hidden" name="car_id" value="<?= (int)$car['id'] ?>">
																							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
																							<button style="width:140px" type="submit">Törlés</button>
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
                                                            <?php endforeach; ?>
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
            </tr>
        </table>
		<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/bottom.php'; ?>
        </td>
    </tr>
</table>
</body>
</html>
