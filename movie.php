<?php
    session_start();
    include_once "lib/lib.php";
    connect();
        
    Login();
    
    $user = $_SESSION["user"];

    $userId = $user['id'];
    $login = $user['login'] ? $user['login'] : $user['vkid'];
    
    $movieId = array_key_exists('id', $_GET) ? (int)$_GET['id'] : -1;
    $movie = false;
    $desc = false;
    $ignore = false;
    $torrents = false;

    if ($movieId != -1) {
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM movies WHERE id = $movieId");
        $movie = mysqli_fetch_assoc($sqlresult);
        $desc = json_decode($movie['description'], true);
        
        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT movieId FROM userignore WHERE userId = $userId AND movieId = $movieId ORDER BY id DESC");
        $ignore = (bool)mysqli_fetch_assoc($sqlresult);

        $sqlresult = mysqli_query($GLOBALS['mysqli'], "SELECT * FROM links WHERE movieId = $movieId ORDER BY added DESC LIMIT 500");
        while ($row = mysqli_fetch_assoc($sqlresult)) {
            //if (qualityToRool($row['quality']) < $user['quality'])
            //    continue;
            //if (translateQualityToRool($row['translateQuality']) < $user['translateQuality'])
            //    continue;
            $torrents[] = $row;
        }
    }

    if ($login == 'wise guest' || $login == 'guest') {
        $userId = -1;
        $login = false;        
    }
?>
<!DOCTYPE html>
<html lang="en">
<?php
    $title = array_key_exists("titleRu",$desc) ? $desc['titleRu'] : $desc['Title'];
    include "html/head.php";
?>

  <body>
    <script src="js/googleApiAuth.js"></script>
    <script type="text/javascript">

        $(document).ready(function() {
            $('#torrentTable').DataTable({
                searching: false,
                paging: false,
                ordering: true,
                order: [[ 6, "desc" ]]
            });
        });

        // Search for a specified string.
        function search() {
          var q = '<?php echo html_entity_decode($title); ?>';
          gapi.client.setApiKey('AIzaSyCBRMNUbFXHHBnQnY0V-hk_PO0xdYAwBio');
          var request = gapi.client.youtube.search.list({
            q: q + " trailer",
            part: 'id'
          });
          request.execute(function(response) {
            var videoId = response.items[0].id.videoId;
            $('#movieTrailer').attr('src', '//www.youtube.com/embed/'+videoId);
            $('.stretchy-wrapper').show();
          });
        }    

        // After the API loads, call a function to enable the search box.
        function handleAPILoaded() {
            search();
        }
    </script>
    <script src="https://apis.google.com/js/client.js?onload=googleApiClientReady"></script>

    <?php
        $liactive = "";
        include "html/navbar.php";
    ?>
    
    <div class="jumbotron">
    <div class="container">
    <?php if ($movie) { ?>
        <div style="float:left; width: 25%">
            <img class="bigPoster" src='<?php echo array_key_exists("PosterRu", $desc)?$desc['PosterRu']:$desc['Poster']; ?>' />
            <table class="movDesc table table-condensed">
            <tbody>
                <tr class="movDescName">
                    <td colspan="2">
                        <?php echo array_key_exists('titleRu', $desc)?$desc['titleRu']:$desc['Title']; ?>
                    </td>
                </tr>
                <tr> 
                    <td colspan="2"> 
                        <?php 
                            echo array_key_exists('titleRu', $desc)?($desc['Title']." "):"";
                            echo $desc['Year'];
                        ?> 
                    </td>
                </tr>
                <tr>
                    <td>IMDB</td>
                    <td>
                    <a title="открыть на IMDB" target='_blank' href='<?php echo "http://www.imdb.com/title/".$movie['imdbid'];?>/'>
                        <?php echo $desc['imdbRating']; ?>
                    </a>
                    </td>
                </tr>
                <tr>
                    <td>КиноПоиск</td>
                    <td>
                    <a title="открыть на КиноПоиске" target='_blank' href='<?php echo "http://www.kinopoisk.ru/film/".$desc['kinopoiskId'];?>/'>
                        <?php echo $desc['kinopoiskRating']; ?>
                    </a>
                    </td>
                </tr>
                <tr>
                    <td>Metascore</td>
                    <td><?php echo $desc['Metascore']; ?></td>
                </tr>
                <tr>
                    <td>премьера</td>
                    <td><?php echo $desc['Released']; ?></td>
                </tr>
                <tr>
                    <td>жанр</td>
                    <td><?php echo $desc['Genre']; ?></td>
                </tr>
                <tr>
                    <td>режиссер</td>
                    <td><?php echo $desc['Director']; ?></td>
                </tr>
                <tr>
                    <td>сценарист</td>
                    <td><?php echo $desc['Writer']; ?></td>
                </tr>
                <tr>
                    <td>актеры</td>
                    <td><?php echo $desc['Actors']; ?></td>
                </tr>
            </tbody>
            </table>
        </div>
        <div style="float:right; width: 75%">
            <div class="stretchy-wrapper" style="display:none;">
                <div>
                    <iframe id="movieTrailer" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
            <table id='torrentTable' class='table table-striped table-hover'>
                <thead>
                    <td>Качество</td>
                    <td>Перевод</td>
                    <td>Ссылка</td>
                    <td>Размер</td>
                    <td>Сиды</td>
                    <td>Личеры</td>
                    <td>Добавлено</td>
                </thead>
                <tbody>
                <?php
                    foreach($torrents as $cur) {
                        echo "<tr>\n";
                        echo "\t<td data-order='" . qualityToRool($cur['quality']) . "'>".$cur['quality']."</td>\n";
                        echo "\t<td data-order='" . translateQualityToRool($cur['translateQuality']) . "'>".$cur['translateQuality']."</td>\n";
                        echo "\t<td><a target='_blank' href='".$cur['link']."'><div class='fullDiv'>".$cur['description']."</div></a></td>\n";
                        echo "\t<td>".$cur['size']."</td>\n";
                        echo "\t<td>".$cur['seed']."</td>\n";
                        echo "\t<td>".$cur['leech']."</td>\n";
                        echo "\t<td data-order='" . strtotime($cur['added']) . "'>".date("M j", strtotime($cur['added']))."</td>\n";
                        echo "</tr>\n";
                    }
                ?>
                </tbody>
            </table>
        </div>
        <div style="clear:both"></div>
    <?php } else
        echo "movie with this id not found"
    ?>

    <?php
        include "html/footer.php";
    ?>
    </div>
    </div>

  </body>
</html>