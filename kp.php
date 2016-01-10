<?php
    // KP page indicator for includes
    define("KPPAGE", true);

    $head_time_start = microtime(true);
    
    require_once "lib/lib.php";
    require_once "lib/sorts.php";
    connect();

    Login();
    $user = $_SESSION["user"];
    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];

    if (array_key_exists('method', $_POST))
        switch ($_POST['method']) {
            case "setSettings":
                setSettings($user, 
                    array("kpID"=>(int)$_POST['kpID'], 
                        "onlyNewTor"=>(int)!empty($_POST['onlyNewTor']), 
                        "quality"=>(int)!empty($_POST['quality']),
                        "translateQuality"=>(int)$_POST['translateQuality'],
                        "sortType" => (int)$_POST['sortType'],
                        )
                );
                //Login();
                break;
            default:
                break;
        }        
    
    $movies = array();

    $movieKPIdList = getKinopoiskMoviesList($user['kpID']);

    $sqlIn = "";
    if (!empty($movieKPIdList))
        foreach($movieKPIdList as $id)
            $sqlIn .= $id . ",";
    $sqlIn .= "-1";
    $q = "SELECT * FROM `movies` WHERE `movies`.kpid in ( " . $sqlIn . " ) AND `movies`.id in (SELECT movieId FROM links)";
    $sqlresult = mysqli_query($GLOBALS['mysqli'], $q);

    $user["sqlIn"] = "";
    while ($row = mysqli_fetch_assoc($sqlresult)) {
        $user["sqlIn"] .= $row['id'] . ",";
        $movies[(int)$row['id']] = $row;
    }
    $user["sqlIn"] .= "-1";

    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }
    
    $keys = sortBySeedLeech($movies, $user);
?>
<!DOCTYPE html>
<html lang="en">
    <?php
        include "html/head.php";
    ?>
  <body>
    <?php
        include "html/userSettings.php";
    ?>

    <?php
        // Head tabs
        // this requires global $liactive pointing at active tab
        $liactive = "kp";
        include "html/navbar.php";
    ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div id='main' class="container-fluid" style="padding: 0">
            <?php if (!$user['kpID']) { ?>
                <p>
                    укажите в фильтре Kinopoisk ID для импорта списка Ваших фильмов из http://www.kinopoisk.ru/user/{ВАШ_KINOPOISK_ID}/movies/
                </p>
                <p style="font-size: 14px">
                    импортируются максимум 200 фильмов <br/>
                    убедитесь, что Ваши списки "публичные" - не скрыты настройками приватности
                </p>
            <?php } else if (empty($keys)) { ?>
                <p>
                    Ваших фильмов нет (либо они непопулярны) на торрентах
                </p>
            <?php } ?>
            
            <?php
                // Divs with movies' posters
                // this requires global $keys
                include "html/movieDivs.php"; 
            ?>
        </div>
        <?php
            include "html/footer.php";
        ?>
    </div>

  </body>
</html>
