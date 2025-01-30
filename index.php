<?php
/*
   +----------------------------------------------------------------------+
   | Stocker uniquement l'id en session est dangereux, si on connait      |
   | l'id d'un autre utilisateur, on peut accéder à ses pages facilement  |
   | sans avoir besoin de se connecter.                                   |
   | De plus, les ids sont séquentiels, ils devraient être changés pour   |
   | des UUID.                                                            |
   +----------------------------------------------------------------------+
 */
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}


$users = require 'user.php';

?>

<h1>Welcome, <?= $users[$_SESSION['user']]['email'] ?></h1>
