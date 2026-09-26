<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/users/functions.php';

/*
 * Felhasználó azonosítása
 */

$sessionUserId = (int)($_SESSION['user_id'] ?? 0);

if ($sessionUserId < 1) {
    http_response_code(403);
    exit('A felhasználó azonosítása sikertelen.');
}

$userId = $sessionUserId;

$isNewUser = false;

/*
 * Admin esetén másik felhasználó szerkesztése
 */

if (isAdmin()) {

    if (isset($_GET['new']) && $_GET['new'] === '1') {

        $isNewUser = true;
        $userId = 0;

        $user = [
            'id'      => 0,
            'user'    => '',
            'name'    => '',
            'address' => '',
            'email'   => '',
            'hash'    => '',
            'role'    => 'user'
        ];

    } elseif (isset($_GET['id'])) {

        $requestedUserId = filter_input(
            INPUT_GET,
            'id',
            FILTER_VALIDATE_INT
        );

        if (
            $requestedUserId !== false &&
            $requestedUserId !== null &&
            $requestedUserId > 0
        ) {
            $userId = $requestedUserId;
        }
    }
}

/*
 * Meglévő felhasználó betöltése
 */

if (!$isNewUser) {

    $user = getUserById($userId);

    if ($user === null) {
        http_response_code(404);
        exit('A felhasználó nem található.');
    }
}
/*
 * Üzenetek
 */

$message = '';
$messageType = '';

/*
 * CSRF token
 */

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['profile_csrf_token'];

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

    /*
     * Profiladatok
     */

    $updatedData = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'address' => trim((string)($_POST['address'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        
    ];
    $email = $updatedData['email'];

if ($email === '') {
    $message = 'Az e-mail cím megadása kötelező.';
    $messageType = 'error';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Kérjük, érvényes e-mail címet adj meg.';
    $messageType = 'error';
}

    /*
     * Jelszómezők
     */

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string)($_POST['new_password_confirm'] ?? '');

    /*
     * Jelszó módosítás csak akkor indul,
     * ha legalább egy mezőt kitöltöttek.
     */

    $passwordChangeRequested =
        $currentPassword !== '' ||
        $newPassword !== '' ||
        $newPasswordConfirm !== '';

    if ($passwordChangeRequested) {

        if ($currentPassword === '') {

            $message = 'A jelenlegi jelszó megadása kötelező.';
            $messageType = 'error';

        } elseif ($newPassword === '') {

            $message = 'Az új jelszó megadása kötelező.';
            $messageType = 'error';

        } elseif ($newPasswordConfirm === '') {

            $message = 'Az új jelszó ismételt megadása kötelező.';
            $messageType = 'error';

        } elseif ($newPassword !== $newPasswordConfirm) {

            $message = 'Az új jelszavak nem egyeznek.';
            $messageType = 'error';

        } elseif (!verifyUserPassword($userId, $currentPassword)) {

            $message = 'A jelenlegi jelszó helytelen.';
            $messageType = 'error';

        } else {

            $updatedData['hash'] = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );
        }
    }

    /*
     * Mentés
     */

    if ($messageType !== 'error') {

        $saved = updateUser(
            $userId,
            $updatedData
        );

        if ($saved) {

            $message = 'A profil adatai sikeresen módosítva.';
            $messageType = 'success';

            $user['name'] = $updatedData['name'];
            $user['address'] = $updatedData['address'];
            $user['email'] = $updatedData['email'];

        } else {

            $message = 'A profil adatainak mentése sikertelen.';
            $messageType = 'error';
        }
    }
}

/*
 * Vissza URL
 */

$backUrl = '/';

?>
<html>
<head>
    <title>RichCars - Profilom</title>
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
                                                            <span class="epc-title">Profilom</span>
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
														<table align="center" class="table-border" width="100%">
															<tr>
																<td class="pl10 epc-text row-odd p5 text-left" style="background-color:#bb271a;color:#ffffff;width:100%;text-align:center;">Adatok
																</td>
															</tr>
														</table>
                                                        <table align="center" style="text-align:left;border-spacing:0px;padding-bottom:20px;" width="100%" class="table-border">
                                                        <!-- Autó neve -->
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Felhasználónév:
																</td>
																<td width="50%" class="row-even p5">
																<?php if (isAdmin()): ?>
																<input type="text" name="username" maxlength="50" value="<?= htmlspecialchars($user['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																<?php else: ?>
																<?= htmlspecialchars($user['user'] ?? '', ENT_QUOTES, 'UTF-8') ?>
																<?php endif; ?>
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Név:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="text" name="name" maxlength="50" value="<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Cím:
																</td>
																<td width="50%" class="row-even p5">
																<input type="text" name="address" maxlength="100" value="<?= htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">E-mail cím:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="email" name="email" maxlength="100" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Jelenlegi jelszó:
																</td>
																<td width="50%" class="row-even p5">
																<input type="password" name="current_password" maxlength="30">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-odd p5">Új jelszó:
																</td>
																<td width="50%" class="row-odd p5">
																<input type="password" name="new_password" maxlength="50">
																</td>
															</tr>
                                                       		<tr>
																<td width="50%" class="epc-text row-even p5">Új jelszó ismét:
																</td>
																<td width="50%" class="row-even p5">
																<input type="password" name="new_password_confirm" maxlength="30">
																</td>
															</tr>
                                                        </table>
                                                        <table class="table-border text-center">
                                                        <!-- Gombok -->
                                                            <tr>
                                                                <td></td>
                                                                <td style="padding-top:10px;">
                                                                <button type="submit">Mentés
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
</body>
</html>
