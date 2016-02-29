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
        <form class="form-inline" action='https://money.yandex.ru/quickpay/confirm.xml' method='post' target='_blank'>  
            <input type='hidden' name='receiver' value='410011013406408'/>
            <!--input type='hidden' name='formcomment' value='formcomment'/-->
            <!--input type='hidden' name='short-dest' value='short-dest'/-->
            <input type='hidden' name='quickpay-form' value='donate'/>
            <input type='hidden' name='paymentType' value='AC'/>
            <div class="input-group">
                <input class="form-control" type="number" name='sum' value='50' step='50' style="width: 70px"/>
                <div class="input-group-addon">₽</div>
            </div>
            <input class="form-control" type='text' name='targets' value='<?php $tmp=array("на кофе", "на пиццу", "на печеньки", "на пивко", "на лимонад");shuffle($tmp);echo reset($tmp);?>'/>
            <button type="submit" class="btn btn-success">поддержать создателей</button>
        </form>
        <br/>
        <p>или пожертвуйте 50₽ в один из фондов</p>
        <?php
            define("allCharity", true);
            include "html/charity.php";
        ?>
    </div>
    <?php
        include "html/footer.php";
    ?>

  </body>
</html>
