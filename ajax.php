<?php
include_once "lib/lib.php";
connect();

Login();
$user = $_SESSION["user"];

$userId = $user['id'];
$login = $user['login'];

if ($user && $login != 'wise guest' && array_key_exists('method', $_POST))
    switch ($_POST['method']) {
        case "ignoreMovie":
            ignoreMovie((int)$user['id'], (int)$_POST['movieId']);
            break;
        case "unIgnoreMovie":
            unIgnoreMovie((int)$user['id'], (int)$_POST['movieId']);
            break;
        default:
            echo "method not specified\n";
    }    

?>