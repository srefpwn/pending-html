<?php

define('LOGIN_PAGE', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/auth.php';


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

<input
    type="text"
    name="user"
    autocomplete="username"
    placeholder="User"
    autofocus
    required
>

<input
    type="password"
    name="password"
    autocomplete="current-password"
    placeholder="Password"
    required
>

<button>Belépés</button>

</form>

</div>

</body>

</html>

<?php
exit;
