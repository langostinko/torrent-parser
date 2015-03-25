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
        include "html/userSettings.php";
    ?>

  <body>
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
                order: [[ 7, "desc" ]],
                info: false,
            });
            $(".movieDelete").click(function( event ) {
              event.preventDefault();
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
            foreach($keys as $key=>$movieSorted) {
                $movie = $movies[$movieSorted['id']];
                $desc = $movie['description'];
                ?>
                <div class='movie moviePos<?php echo $movie['id']; ?>'>
                    <a title="<?php echo array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']; echo " (".$movie['totalSeed']."↑ ".$movie['totalLeech']."↓)"; ?>" target='_blank' href="movie.php?id=<?php echo $movie['id']; ?>">
                        <img class='poster' src='<?php echo array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']; ?>' />
                    </a>
                    <?php if (array_key_exists("kinopoiskId", $desc)) {?>
                        <a title="открыть на Кинопоиске" target='_blank' href='<?php echo "http://www.kinopoisk.ru/film/".$desc['kinopoiskId'];?>/'> 
                    <?php } else { ?>
                        <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movie['imdbid'];?>/'> 
                    <?php } ?>
                        <div class='movieInfo'>
                            <div class='movieRating'><?php echo sprintf("%.1f", (array_key_exists("kinopoiskRating", $desc)&&$desc['kinopoiskRating'])?$desc['kinopoiskRating']:$desc['imdbRating'] ); ?></div>
                            <div class='movieRelease'>
                                <?php echo date("M'y",$movie['Release']); ?>
                                <!--<?php echo $movie['totalSeed']."↑ ".$movie['totalLeech']."↓"; ?>-->
                            </div>
                        </div>
                    </a>
                    <div class='movieTitle'>
                        <?php echo array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']; ?>
                        <div class='movieQuality'>
                            <span class="glyphicon glyphicon-facetime-video"></span> <?php echo $movie['qualityStr']; ?>
                            <span class="glyphicon glyphicon-volume-up"></span> <?php echo $movie['translateQualityStr'];/*translateQualityToStr($movie['translateQuality']);*/ ?>
                        </div>
                    <!--
                    -->
                    </div>
                    <?php if ($login) { ?>
                    <a title="не показывать (в корзину)" target='_blank' href='#'> 
                        <div class='movieDelete' movieId='<?php echo $movie['id']; ?>'>
                            <span class="glyphicon glyphicon-remove-circle"></span>
                        </div>
                    </a>
                    <?php } ?>
                </div>
                <?php
                    if (++$cnt >= 48)
                        break;
            }    
        ?>    

            <h2 class="hidden-xs">Свежие торренты</h2>
            <table id='torrentTable' class='table table-striped table-hover hidden-xs' cellspacing="0" width="100%">
                <thead>
                    <td>качество</td>
                    <td>перевод</td>
                    <td>фильм</td>
                    <td>размер</td>
                    <td>сиды</td>
                    <td>личеры</td>
                    <td>добавлено</td>
                </thead>
                <tbody>
                <?php
                    $torrents = array();
                    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE 1 ORDER BY added DESC LIMIT 10");
                    while ($row = mysqli_fetch_assoc($sqlresult))
                        $torrents[] = $row;

                    foreach($torrents as $cur) {
                        echo "<tr>\n";
                        echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
                        echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
                        echo "\t<td><a target='_blank' href='/movie.php?id=".$cur['movieId']."'>".$cur['description']."</a></td>\n";
                        echo "\t<td>".$cur['size']."</td>\n";
                        echo "\t<td>".$cur['seed']."</td>\n";
                        echo "\t<td>".$cur['leech']."</td>\n";
                        echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M j H:i:s", strtotime($cur['added']))."</td>\n";
                        echo "</tr>\n";
                    }
                ?>
                </tbody>
            </table>      
        </div>
    </div>

    <?php
        include "html/footer.php";
    ?>


    </div> <!-- /container -->
    
  </body>
</html>
