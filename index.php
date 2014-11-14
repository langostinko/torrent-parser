<?php
    session_start();
    include_once "lib/lib.php";
    connect();

    vkAuth();

    if (array_key_exists("logout", $_GET)) {
        if(isset($_SESSION['user']))
            unset($_SESSION['user']);       
        if(isset($_SESSION['showSettings']))
            unset($_SESSION['showSettings']);      
        header("Location: http://cinema.todeliver.ru/"); 
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
                        "quality"=>(int)!empty($_POST['quality']),
                        "translateQuality"=>(int)$_POST['translateQuality'],
                        )
                );
                //Login();
                break;
            default:
                break;
        }        
    

    $result = array();
    $movies = array();
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $movies[(int)$row['id']] = $row;
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieId FROM userignore WHERE userId = $userId");
    $ignore = array();
    while ($row = mysqli_fetch_assoc($sqlresult))
        $ignore[$row['movieId']] = true;

    $newMov = array();    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links ORDER BY added DESC LIMIT 500");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!array_key_exists($row['movieId'], $ignore)) {
            if (qualityToRool($row['quality']) < $user['quality'])
                continue;
            if (translateQualityToRool($row['translateQuality']) < $user['translateQuality'])
                continue;
            if (!is_array($movies[$row['movieId']]['description']))
                $movies[$row['movieId']]['description'] = json_decode($movies[$row['movieId']]['description'], true);
            if ((float)$movies[$row['movieId']]['description']['imdbRating'] < $user['minRating'])
                continue;
            if ($user['minVotes']) {
                $votes = intval(str_replace(",","",$movies[$row['movieId']]['description']['imdbVotes']));;
                if ($votes < $user['minVotes']) 
                    continue;
            }

            if (empty($movies[$row['movieId']]['Release']))
                $movies[$row['movieId']]['Release'] = strtotime($movies[$row['movieId']]['description']['Released']);

            if ($user['maxDaysDif']) {
                if ((time()-$movies[$row['movieId']]['Release'])/(30.417*24*60*60) > $user['maxDaysDif']) 
                    continue;
            }
            if (!(array_key_exists("Poster", $movies[$row['movieId']]['description']) && $movies[$row['movieId']]['description']['Poster'] != 'N/A'))
                continue;

            $newMov[$row['movieId']] = true;
            $result[] = $row;
        }
        
    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }

?>
<!DOCTYPE html>
<html lang="en">
    <?php
        $title = "Cinema : Новые фильмы на торрентах";
        include "html/head.php";
        include "html/userSettings.php";
    ?>

  <body>
    <script type="text/javascript">
        var userId = <?php echo $userId; ?>;

        function ignoreMovie(movieId) {
            $.post( "ajax.php", { method: "ignoreMovie", userId: userId, movieId: movieId })
                .done(function( data ) {

            });
        }

        $(document).ready(function() {
            $(".movieDelete").click(function( event ) {
              ignoreMovie($(this).attr('movieId'));
            });
        });  
  </script>

    
    <?php
        $liactive = "home";
        include "html/navbar.php";
    ?>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div id='main' class="container-fluid" style="padding: 0">
        <?php
            $cnt = 0;
            foreach(array_keys($newMov) as $key) {
                $desc = $movies[$key]['description'];
                if (array_key_exists("Poster", $desc) && $desc['Poster'] != 'N/A') {
                ?>
                    <div class='movie moviePos<?php echo $key; ?>'>
                        <a title="<?php echo $desc['Title'] ?>" href="/movie.php?id=<?php echo $key; ?>">
                            <img class='poster' src='<?php echo $desc['Poster']; ?>' />
                        </a>
                        <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movies[$key]['imdbid'];?>/'> 
                            <div class='movieInfo'>
                                <div class='movieRating'><?php echo $desc['imdbRating']; ?></div>
                                <div class='movieRelease'><?php echo date("M'y",$movies[$key]['Release']); ?></div>
                            </div>
                        </a>
                        <?php if ($login) { ?>
                        <a title="не показывать (в корзину)" target='_blank' href='#'> 
                            <div class='movieDelete' movieId='<?php echo $key ?>'>
                                <span class="glyphicon glyphicon-remove-circle"></span>
                            </div>
                        </a>
                        <?php } ?>
                    </div>
                <?php
                    if (++$cnt >= 24)
                        break;
                }
            }    
        ?>    
      </div>
    </div>

    <?php
        include "html/footer.php";
    ?>


    </div> <!-- /container -->
    
  </body>
</html>
