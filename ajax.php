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
            if (isAdmin($user['id'])) {
                echo vkUploadPhoto((int)$_POST['movieId'], $user['token']);
            } else echo "access denied";
            break;
        case "updateMovie":
            if (isAdmin($user['id'])) {
                $movie = array();
                if ($_POST['imdbid']) $movie['imdbid'] = $_POST['imdbid'];
                if ($_POST['kpid']) $movie['kpid'] = $_POST['kpid'];
                $res = addMovie($movie, true);
                echo ($res===0)?"UPDATED\n":"NOT UPDATED: $res\n";
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
                    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id = $movieId");
                    $row = mysqli_fetch_assoc($sqlresult);
                    $desc = json_decode($row['description'], true) ?: array();
                    $desc = array_merge($desc, $movie['movie']['description']);
                    $desc = mysqli_real_escape_string($GLOBALS['mysqli'], json_encode($desc, JSON_UNESCAPED_UNICODE));
                    mysqli_query($GLOBALS['mysqli'], "UPDATE movies SET kpid='$kpid', imdbid='$imdbid', description='$desc' WHERE id = $movieId");
                    echo mysqli_error($GLOBALS['mysqli']);
                }
            } else echo "access denied";
            break;
        case "getLogs":
            if (isAdmin($user['id'])) {
                $log = `tail -n 100 logs/\$(ls -t logs | head -1)`;
                echo $log;
            } else echo "access denied";
            break;
        default:
            echo "method not specified\n";
    }    

?>