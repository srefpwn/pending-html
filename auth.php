<?php
session_set_cookie_params([
    'lifetime' => 0,
    'httponly' => true,
    'secure' => true,      // csak HTTPS esetén
    'samesite' => 'Strict'
]);
session_start();

/*
 * password_hash('AzEnTitkosJelszavam', PASSWORD_DEFAULT)
 * eredménye.
 */
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
    isset($_SESSION['authenticated'], $_SESSION['last_activity']) &&
    $_SESSION['authenticated'] === true
) 	{

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

// Belépési kísérlet
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['user'], $_POST['password'])
    && $_POST['user'] !== ''
    && $_POST['password'] !== '') {

$loginUser = strtolower(trim($_POST['user']));

foreach ($USERS as $account) {

    if (
        $account['user'] === $loginUser
        && password_verify($_POST['password'], $account['hash'])
    ) {

        session_regenerate_id(true);

        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['user'] = $account['user'];
        $_SESSION['profile_name'] = $account['name'];
        $_SESSION['role'] = $account['role'];
        $_SESSION['last_activity'] = time();

        header("Location: /");
        exit;
    }
}

$error = true;

}

?>
<!doctype html>
<html lang="hu">
<head>
<meta charset="utf-8">
<title>Bejelentkezés</title>
<style>
body{
    font-family:Arial;
    background:#eee;
}
.box{
    width:320px;
    margin:120px auto;
    background:#fff;
    padding:25px;
    border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,.2);
}
input{
    width:100%;
    padding:10px;
    box-sizing:border-box;
}
button{
    margin-top:10px;
    width:100%;
    padding:10px;
}
.error{
    color:red;
}
</style>
</head>
<body>

<div class="box">

<h2>Bejelentkezés</h2>

<?php if($error): ?>
<div class="error">Hibás jelszó.</div>
<?php endif; ?>

<form method="post">
   <input type="text" name="user" autocomplete="username" placeholder="User" autofocus required>
   <input type="password" name="password" autocomplete="current-password" placeholder="Password" required>
    <button>Belépés</button>
</form>

</div>

</body>
</html>
<?php
exit;
