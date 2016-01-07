<?php
    //header('Content-Type: text/plain; charset=UTF-8');

    $head_time_start = microtime(true);
    
    require_once "lib/lib.php";
    require_once "lib/sorts.php";
    connect();

    vkAuth();

    if (array_key_exists("logout", $_GET)) {
        if(isset($_SESSION['user']))
            unset($_SESSION['user']);       
        if(isset($_SESSION['showSettings']))
            unset($_SESSION['showSettings']);      
        header("Location: " . \pass\VK::$redirect_uri); 
    }

    Login();
    $user = $_SESSION["user"];
    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];

    if (array_key_exists('method', $_POST))
        switch ($_POST['method']) {
            case "setSettings":
                setSettings($user, 
                    array("minRating"=>(float)$_POST['minRating'], 
                        "minVotes"=>(int)$_POST['minVotes'], 
                        "maxDaysDif"=>(int)$_POST['maxDaysDif'], 
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

    $q = "SELECT * FROM `movies` WHERE `movies`.id in (SELECT movieId FROM links) AND NOT `movies`.id in (SELECT movieId FROM userignore WHERE userId=$userId)";
    $sqlresult = mysqli_query($GLOBALS['mysqli'], $q);
    
    while ($row = mysqli_fetch_assoc($sqlresult))
        $movies[(int)$row['id']] = $row;

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

    <script type="text/javascript">
        var userId = <?php echo $userId; ?>;

        function ignoreMovie(movieId) {
            $.post( "ajax.php", { method: "ignoreMovie", userId: userId, movieId: movieId })
                .done(function( data ) {
                $('.moviePos'+movieId).remove();
            });
        }

        $(document).ready(function() {
            $('#torrentTable').DataTable({
                searching: false,
                paging: false,
                ordering: true,
                order: [[ 6, "desc" ]],
                info: false,
            });
            $(".movieDelete").click(function( event ) {
              event.preventDefault();
              ignoreMovie($(this).attr('movieId'));
            });
        });  
    </script>
    
    <?php
        // Head tabs
        // this requires global $liactive pointing at active tab
        $liactive = "home";
        include "html/navbar.php";
    ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div id='main' class="container-fluid" style="padding: 0">
            <?php
                // Divs with movies' posters
                // this requires global $keys
                include "html/movieDivs.php"; 
            ?>

            <?php
                // Fresh torrents table
                include "html/freshTorrents.php"; 
            ?>
        </div>
        <?php
            include "html/footer.php";
        ?>
    </div>

  </body>
</html>
