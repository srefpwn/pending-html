<?php

define(
    'USERS_FILE',
    $_SERVER['DOCUMENT_ROOT'] . '/data/users/users.json'
);

function loadUsers(): array
{
    if (!file_exists(USERS_FILE)) {
        return [];
    }

    $json = file_get_contents(USERS_FILE);

    if ($json === false || trim($json) === '') {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function saveUsers(array $users): bool
{
    $json = json_encode(
        $users,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return false;
    }

    return file_put_contents(
        USERS_FILE,
        $json,
        LOCK_EX
    ) !== false;
}

function getUserById(int $userId): ?array
{
    $users = loadUsers();

    foreach ($users as $user) {

        if (
            isset($user['id']) &&
            (int)$user['id'] === $userId
        ) {
            return $user;
        }
    }

    return null;
}

function updateUser(
    int $userId,
    array $updatedData
): bool {
    $users = loadUsers();

    foreach ($users as $index => $user) {

        if (
            isset($user['id']) &&
            (int)$user['id'] === $userId
        ) {
            foreach ($updatedData as $key => $value) {

                if ($key === 'id') {
                    continue;
                }

                $users[$index][$key] = $value;
            }

            return saveUsers($users);
        }
    }

    return false;
}

function verifyUserPassword(
    int $userId,
    string $password
): bool {
    $user = getUserById($userId);

    if (
        $user === null ||
        !isset($user['hash'])
    ) {
        return false;
    }

    return password_verify(
        $password,
        $user['hash']
    );
}

function deleteUser(int $userId): bool
{
    $users = loadUsers();

    foreach ($users as $index => $user) {

        if (
            isset($user['id']) &&
            (int)$user['id'] === $userId
        ) {
            unset($users[$index]);

            $users = array_values($users);

            return saveUsers($users);
        }
    }

    return false;
}
