<?php
    $head_time_start = microtime(true);
    
    require_once "lib/lib.php";
    require_once "lib/sorts.php";
    connect();

    Login();
    $user = $_SESSION["user"];
    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];

    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }

?>
<!DOCTYPE html>
<html lang="en">
    <?php
        include "html/head.php";
    ?>
  <body>
    <?php
        // Head tabs
        // this requires global $liactive pointing at active tab
        include "html/navbar.php";
    ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div id='main' class="container-fluid" style="padding: 0">
        <h2>Очищение кармы</h2>
        <?php
            define("allCharity", true);
            include "html/charity.php";
        ?>
        <p>пожертвуйте 50₽ в один из фондов</p>
    </div>
    <?php
        include "html/footer.php";
    ?>

  </body>
</html>
