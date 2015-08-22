<?php
include_once "lib/lib.php";
connect();

if (array_key_exists("search", $_GET)) {
    $result = array();
    $request = mysqli_escape_string($GLOBALS['mysqli'], $_GET['search']);
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM `movies` WHERE search LIKE '%$request%' AND `movies`.id in (SELECT movieId FROM links) ORDER BY sum_peers DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        $desc = json_decode($row['description'], True);
        $result[] = array(
            "id"=>$row['id'],
            "value"=>array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title'], 
            "sum_peers"=>$row['sum_peers'],
            "year"=> $desc['Year'],
        );
    }
    echo json_encode($result);
    return;
}

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
        case "vkUploadPhoto":
            echo vkUploadPhoto((int)$_POST['movieId'], $user['token']);
            break;
        case "updateMovie":
            if (isAdmin($user['id'])) {
                $movie = array();
                if ($_POST['imdbid']) $movie['imdbid'] = $_POST['imdbid'];
                if ($_POST['kpid']) $movie['kpid'] = $_POST['kpid'];
                $res = addMovie($movie, true);
                echo $res?"UPDATED\n":"NOT UPDATED\n";
                print_r($movie);
            } else echo "access denied";
            break;
        case "getIds":
            if (isAdmin($user['id'])) {
                $movie = array();
                if ($_POST['year']) $movie['year'] = (int)$_POST['year'];
                getIds($_POST['title'], $movie);
                print_r($movie);
                if ($_POST['movieId']) {
                    $movieId = (int)$_POST['movieId'];
                    $kpid = mysqli_real_escape_string($GLOBALS['mysqli'], $movie['movie']['kpid']);
                    $imdbid = mysqli_real_escape_string($GLOBALS['mysqli'], $movie['movie']['imdbid']);
                    echo "Updating movie $movieId: kp $kpid, imdb $imdbid\n";
                    mysqli_query($GLOBALS['mysqli'], "UPDATE movies SET kpid='$kpid', imdbid='$imdbid' WHERE id = $movieId");
                    echo mysqli_error($GLOBALS['mysqli']);
                }
            } else echo "access denied";
            break;
        default:
            echo "method not specified\n";
    }    

?>