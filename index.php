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
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies");
    while ($row = mysqli_fetch_assoc($sqlresult))
        $movies[(int)$row['id']] = $row;
    
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieId FROM userignore WHERE userId = $userId");
    $ignore = array();
    while ($row = mysqli_fetch_assoc($sqlresult))
        $ignore[$row['movieId']] = true;

    $newMov = array();    
    $take = array();
    $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links ORDER BY seed+leech DESC");
    while ($row = mysqli_fetch_assoc($sqlresult))
        if (!array_key_exists($row['movieId'], $ignore)) {
            if (!array_key_exists($row['movieId'], $take) || !$take[$row['movieId']]) {
                $newMov[] = $row['movieId'];
                $take[$row['movieId']] = 1;
            }
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
            $take[$row['movieId']] = 2;
        }
        
    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }

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
            foreach($newMov as $key) 
            if ($take[$key] == 2) {
                $desc = $movies[$key]['description'];
                ?>
                <div class='movie moviePos<?php echo $key; ?>'>
                    <a title="<?php echo array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']; ?>" href="/movie.php?id=<?php echo $key; ?>">
                        <img class='poster' src='<?php echo array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']; ?>' />
                    </a>
                    <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movies[$key]['imdbid'];?>/'> 
                        <div class='movieInfo'>
                            <div class='movieRating'><?php echo $desc['imdbRating']; ?></div>
                            <div class='movieRelease'><?php echo date("M'y",$movies[$key]['Release']); ?></div>
                        </div>
                    </a>
                    <div class='movieTitle'>
                        <?php echo array_key_exists("titleRu", $desc)?$desc['titleRu']:$desc['Title']; ?>
                    </div>
                    <?php if ($login) { ?>
                    <a title="не показывать (в корзину)" target='_blank' href='#'> 
                        <div class='movieDelete' movieId='<?php echo $key ?>'>
                            <span class="glyphicon glyphicon-remove-circle"></span>
                        </div>
                    </a>
                    <?php } ?>
                </div>
                <?php
                    if (++$cnt >= 72)
                        break;
            }    
        ?>    

            <h2>Свежие торренты</h2>
            <table id='torrentTable' class='table table-striped table-hover'>
                <thead>
                    <td>качество</td>
                    <td>перевод</td>
                    <td>фильм</td>
                    <td>скачать торрент</td>
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
                        if (!is_array($movies[$cur['movieId']]['description']))
                            $movies[$cur['movieId']]['description'] = json_decode($movies[$cur['movieId']]['description'], true);
                        echo "<tr>\n";
                        echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
                        echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
                        echo "\t<td><a target='_blank' href='/movie.php?id=".$cur['movieId']."'><div class='fullDiv'>";
                        echo array_key_exists("titleRu",$movies[$cur['movieId']]['description']) ? $movies[$cur['movieId']]['description']['titleRu'] : $movies[$cur['movieId']]['description']['Title'];
                        echo "</div></a></td>\n";
                        echo "\t<td><a target='_blank' href='".$cur['link']."'>".$cur['description']."</a></td>\n";
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
