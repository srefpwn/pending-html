<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'secure' => true,      // csak HTTPS esetén
    'samesite' => 'Strict'
]);
session_start();

$USERS = [
    [
        'id' => 1,
        'user' => 'admin',
        'name' => 'Gutter Richárd',   // csak megjelenítés
        'hash' => '$2y$10$Acd1efBVagwASefYF.PkAu3/FdirfjJXMg40Ije47pdcB8/xJRPie',
        'role' => 'admin'
    ],
        [
        'id' => 2,
        'user' => 'sref',
        'name' => 'teszt',   // csak megjelenítés
        'hash' => '$2y$10$Acd1efBVagwASefYF.PkAu3/FdirfjJXMg40Ije47pdcB8/xJRPie',
        'role' => 'user'
    ]
];


const SESSION_TIMEOUT = 1800; // 30 perc

function isAuthenticated(): bool
{
    return isset($_SESSION['authenticated'])
        && $_SESSION['authenticated'] === true;
}

function isAdmin(): bool
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}

function isUser(): bool
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === 'user';
}


// Ha már be van jelentkezve
if (
    isset($_SESSION['authenticated'], $_SESSION['last_activity']) &&
    $_SESSION['authenticated'] === true
) {

    // Session lejárata
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

    } else {

        $_SESSION['last_activity'] = time();

        return;
    }
}


// Ha nincs bejelentkezve, a login oldalra megy
if (!isAuthenticated() && !defined('LOGIN_PAGE')) {
    header('Location: /login.php');
    exit;
}
