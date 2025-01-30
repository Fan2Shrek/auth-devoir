<?php

session_start();

$users = require 'user.php';

if (isset($_POST['submit']) && in_array("", [$_POST['username'], $_POST['password']])) {
    $error = "Please fill in your username and password";
}

if (isset($_POST['submit']) && !isset($error)) {
    foreach ($users as $k => $user) {
        if ($user['email'] === $_POST['username'] && $user['password'] === $_POST['password']) {
            $_SESSION['user'] = $k;
            header('Location: index.php');
            exit;
        }
    }
    $error = "Invalid username or password";
}

?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" value="<?= $_POST['username'] ?? "" ?>">
    <input type="password" name="password" placeholder="Password">
    <button name="submit" type="submit">Login</button>
    <p><?= $error ?? "" ?></p>
</form>
