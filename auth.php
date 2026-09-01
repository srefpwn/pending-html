<?php

session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);

session_start();


$USERS = [
    [
        'id' => 1,
        'user' => 'admin',
        'name' => 'Gutter Richárd',   // csak megjelenítés
        'hash' => '$asd',
        'role' => 'admin'
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
    isset($_SESSION['authenticated'], $_SESSION['last_activity'])
    && $_SESSION['authenticated'] === true
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

if (!isAuthenticated()) {
    header('Location: /login.php');
    exit;
}
